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
        'pan',
        'secret',
        'credentials',
        'card[number]',
        'password',
        'auth',
        'access_token',
        'accesstoken',
        'authorization',
        'api_key',
        'apikey',
        'token',
        'jwt',
        'session',
        'sessionid',
        'session_id',
        'client_secret',
        'private_key',
        'ssh_key',
        'key',
        'creditcard',
        'credit_card',
        'pin',
        'ssn',
        'security_code',
        'x-api-key',
        'x-auth-token',
        'bearer',
    ];

    /**
     * Regex for bank card PAN numbers
     *
     * @var string
     */
    private $bankCardRegex = '/^(?:4[0-9]{12}(?:[0-9]{3})?|[25][1-7][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$/';

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