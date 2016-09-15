<?php namespace CodeIgniter\Validation;

/**
 * Class CreditCardRules
 *
 * Provides validation methods for common credit-card inputs.
 *
 * @see http://en.wikipedia.org/wiki/Credit_card_number
 *
 * @package CodeIgniter\Validation
 */
class CreditCardRules
{
    /**
     * The cards that we support, with the defining details:
     *
     *  name        - The type of card as found in the form. Must match the user's value
     *  length      - List of possible lengths for the card number
     *  prefixes    - List of possible prefixes for the card
     *  checkdigit  - Boolean on whether we should do a modulus10 check on the numbers.
     *
     * @var array
     */
    protected $cards = [
        'American Express'          => ['name' => 'amex', 'length' => '15', 'prefixes' => '34,37', 'checkdigit' => true],
        'China UnionPay'            => ['name' => 'unionpay', 'length' => '16,17,18,19', 'prefixes' => '62', 'checkdigit' => true],
        'DinersClub CarteBlanche'   => ['name' => 'carteblanche', 'length' => '14', 'prefixes' => '300,301,302,303,304,305', 'checkdigit' => true],
        'DinersClub'                => ['name' => 'dinersclub', 'length' => '14,16', 'prefixes' => '300,301,302,303,304,305,309,36,38,39,54,55', 'checkdigit' => true],
        'Discover Card'             => ['name' => 'discover', 'length' => '16,19', 'prefixes' => '6011,622,644,645,656,647,648,649,65', 'checkdigit' => true],
        'InterPayment'              => ['name' => 'interpayment', 'length' => '16,17,18,19', 'prefixes' => '4', 'checkdigit' => true],
        'JCB'                       => ['name' => 'jcb', 'length' => '16', 'prefixes' => '352,353,354,355,356,357,358', 'checkdigit' => true],
        'Maestro'                   => ['name' => 'maestro', 'length' => '12,13,14,15,16,18,19', 'prefixes' => '50,56,57,58,59,60,61,62,63,64,65,66,67,68,69', 'checkdigit' => true],
        'Dankort'                   => ['name' => 'dankort', 'length' => '16', 'prefixes' => '5019,4175,4571,4', 'checkdigit' => true],
        'NSPK MIR'                  => ['name' => 'mir', 'length' => '16', 'prefixes' => '2200,2201,2202,2203,2204', 'checkdigit' => true],
        'MasterCard'                => ['name' => 'mastercard', 'length' => '16', 'prefixes' => '51,52,53,54,55,22,23,24,25,26,27', 'checkdigit' => true],
        'Visa'                      => ['name' => 'visa', 'length' => '13,16,19', 'prefixes' => '4', 'checkdigit' => true],
        'UATP'                      => ['name' => 'uatp', 'length' => '15', 'prefixes' => '1', 'checkdigit' => true],
        'Verve'                     => ['name' => 'verve', 'length' => '16,19', 'prefixes' => '506,650', 'checkdigit' => true],

        // Canadian Cards
        'CIBC Convenience Card'             => ['name' => 'cibc', 'length' => '16', 'prefixes' => '4506', 'checkdigit' => false],
        'Royal Bank of Canada Client Card'  => ['name' => 'rbc', 'length' => '16', 'prefixes' => '45', 'checkdigit' => false],
        'TD Canada Trust Access Card'       => ['name' => 'tdtrust', 'length' => '16', 'prefixes' => '589297', 'checkdigit' => false],
        'Scotiabank Scotia Card'            => ['name' => 'scotia', 'length' => '16', 'prefixes' => '4536', 'checkdigit' => false],
        'BMO ABM Card'                      => ['name' => 'bmoabm', 'length' => '16', 'prefixes' => '500', 'checkdigit' => false],
        'HSBC Canada Card'                  => ['name' => 'hsbc', 'length' => '16', 'prefixes' => '500', 'checkdigit' => false],
    ];

    //--------------------------------------------------------------------

    /**
     * Verifies that a credit card number is valid and matches the known
     * formats for a wide number of credit card types. This does not verify
     * that the card is a valid card, only that the number is formatted correctly.
     *
     * Example:
     *  $rules = [
     *      'cc_num' => 'valid_cc_number[visa]'
     *  ];
     *
     * @param string $ccNumber
     * @param string $type
     * @param array  $data
     *
     * @return bool
     */
    public function valid_cc_number(string $ccNumber, string $type, array $data): bool
    {
        $type = strtolower($type);
        $info = null;

        // Get our card info based on provided name.
        foreach ($this->cards as $card)
        {
            if ($card['name'] == $type)
            {
                $info = $card;
            }
        }

        // If empty, it's not a card type we recognize, or invalid type.
        if (empty($info))
        {
            return false;
        }

        // Make sure we have a valid length
        if (mb_strlen($ccNumber) === 0)
        {
            return false;
        }

        // Remove any spaces and dashes
        $ccNumber = str_replace([' ', '-'], '', $ccNumber);

        // Non-numeric values cannot be a number...duh
        if (! is_numeric($ccNumber))
        {
            return false;
        }

        // Make sure it's a valid length for this card
        $lengths = explode(',', $info['length']);

        if (! in_array(mb_strlen($ccNumber), $lengths))
        {
            return false;
        }

        // Make sure it has a valid prefix
        $prefixes = explode(',', $info['prefixes']);

        $validPrefix = false;

        foreach ($prefixes as $prefix)
        {
            if ($prefix == mb_substr($ccNumber, 0, mb_strlen($prefix)))
            {
                $validPrefix = true;
            }
        }

        if ($validPrefix === false)
        {
            return false;
        }

        // Still here? Then check the number against the Luhn algorithm, if required
        // @see https://en.wikipedia.org/wiki/Luhn_algorithm
        // @see https://gist.github.com/troelskn/1287893
        if ($info['checkdigit'] === true)
        {
            return $this->isValidLuhn($ccNumber);
        }

        return true;
    }

    //--------------------------------------------------------------------

    /**
     * Checks the given number to see if the number passing a Luhn check.
     *
     * @param string $num
     *
     * @return bool
     */
    protected function isValidLuhn(string $number): bool
    {
        settype($number, 'string');

        $sumTable = [
            [0,1,2,3,4,5,6,7,8,9],
            [0,2,4,6,8,1,3,5,7,9]
        ];

        $sum = 0;
        $flip = 0;

        for ($i = strlen($number) - 1; $i >= 0; $i--)
        {
            $sum += $sumTable[$flip++ & 0x1][$number[$i]];
        }

        return $sum % 10 === 0;
    }

    //--------------------------------------------------------------------

}
