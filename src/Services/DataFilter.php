<?php

declare(strict_types=1);

namespace HawkBundle\Services;

/**
 * Sensitive data filter for Hawk context and breadcrumbs.
 */
class DataFilter
{
    /**
     * Placeholder for sensitive values
     */
    private $filteredValuePlaceholder = '[filtered]';

    /**
     * Possibly sensitive keys (case-insensitive)
     *
     * @var array<string>
     */
    private $possiblySensitiveDataKeys = [
        // Authorization and sessions
        'auth',
        'authorization',
        'access_token',
        'accesstoken',
        'token',
        'jwt',
        'session',
        'sessionid',
        'session_id',

        // API keys and secure tokens
        'api_key',
        'apikey',
        'x-api-key',
        'x-auth-token',
        'bearer',
        'client_secret',
        'secret',
        'credentials',

        // Passwords
        'password',
        'passwd',
        'mysql_pwd',

        // Encryption keys
        'private_key',
        'ssh_key',

        // Payments data
        'card',
        'cardnumber',
        'card[number]',
        'creditcard',
        'credit_card',
        'pan',
        'pin',
        'security_code',
        'stripetoken',
        'cloudpayments_public_id',
        'cloudpayments_secret',

        // Connections
        'dsn',

        // Personal Data
        'ssn',
    ];

    /**
     * Regex for bank card PAN numbers
     *
     * @var string
     */
    // Regex patterns for different card types:
    // Visa: 13 or 16 digits, starting with 4
    private $visaRegex = '4[0-9]{12}(?:[0-9]{3})?';
    // MasterCard: 16 digits, starting with 51-55 or 2221-2720
    private $masterCardRegex = '(?:5[1-5][0-9]{14}|2[2-7][0-9]{14})';
    // American Express: 15 digits, starting with 34 or 37
    private $amexRegex = '3[47][0-9]{13}';
    // Discover: 16 digits, starting with 6011 or 65
    private $discoverRegex = '6(?:011|5[0-9][0-9])[0-9]{12}';
    // Diners Club: 14 digits, starting with 300-305, 36, or 38
    private $dinersRegex = '3(?:0[0-5]|[68][0-9])[0-9]{11}';
    // JCB: 15 or 16 digits, starting with 2131, 1800, or 35
    private $jcbRegex = '(?:2131|1800|35\d{3})\d{11}';
    // Combined regex for all supported card types
    private $bankCardRegex = '/^('
        . $this->visaRegex . '|'
        . $this->masterCardRegex . '|'
        . $this->discoverRegex . '|'
        . $this->amexRegex . '|'
        . $this->dinersRegex . '|'
        . $this->jcbRegex
        . ')$/';

    /**
     * Recursively filter sensitive data in array
     *
     * @param mixed $data
     * @return mixed
     */
    public function process($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                // PAN check
                $value = $this->filterPanNumbers($value);

                // Sensitive keys check
                $value = $this->filterSensitiveData((string) $key, $value);

                // Recurse into arrays
                if (is_array($value)) {
                    $value = $this->process($value);
                }

                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * Replace PAN numbers in values
     *
     * @param mixed $value
     * @return mixed
     */
    private function filterPanNumbers($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $clean = preg_replace('/\D/', '', $value);

        if ($clean && preg_match($this->bankCardRegex, $clean)) {
            return $this->filteredValuePlaceholder;
        }

        return $value;
    }

    /**
     * Filter values with sensitive keys
     *
     * @param string $key
     * @param mixed  $value
     * @return mixed
     */
    private function filterSensitiveData(string $key, $value)
    {
        if (in_array(strtolower($key), $this->possiblySensitiveDataKeys, true)) {
            if (is_array($value)) {
                return $this->replaceRecursive($value);
            }

            return $this->filteredValuePlaceholder;
        }

        return $value;
    }

    /**
     * Recursively replace all values in array with placeholder
     *
     * @param array $arr
     * @return array
     */
    private function replaceRecursive(array $arr): array
    {
        foreach ($arr as $k => &$v) {
            if (is_array($v)) {
                $v = $this->replaceRecursive($v);
            } else {
                $v = $this->filteredValuePlaceholder;
            }
        }
        return $arr;
    }
}
