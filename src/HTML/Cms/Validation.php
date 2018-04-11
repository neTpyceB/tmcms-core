<?php
declare(strict_types=1);

namespace TMCms\HTML\Cms;

use TMCms\Strings\Verify;

\defined('INC') or exit;

/**
 * Class Validation
 * @package TMCms\HTML\Cms
 */
class Validation
{
    private static $messages = [
        'required' => 'Field value is required',
        'min_length' => 'Value length have to be more then %s',
        'max_length' => 'Value length have to be less then %s',
        'min' => 'Value have to be more then %s',
        'max' => 'Value have to be less then %s',
        'equal_to_field' => 'Value have to be equal to %s',
        'email' => 'Have to be email',
        'number' => 'Have to be number',
        'integer' => 'Have to be integer',
        'digits' => 'Have to contain only digits',
        'alphanum' => 'Can contain letters and digits only',
        'url' => 'Have to be url',
        'empty' => 'Empty against bots',
    ];

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     *
     * @return array
     */
    public static function validate(array $data, array $rules = [], array $messages = []): array
    {
        $errors = [];

        foreach ($rules as $name => $field_rules) {
            if (isset($data[$name])) {
                $field_value = $data[$name];

                $found_err = self::validateFiled($data, $field_value, self::normalizeRules($field_rules), $messages[$name] ?? []);

                if(\is_array($found_err)){
                    $errors[$name] = $found_err;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array $data
     * @param string $field_value
     * @param array $field_rules
     * @param array $messages
     *
     * @return array|bool
     */
    public static function validateFiled(array $data, string $field_value, array $field_rules, array $messages = [])
    {
        $errors = [];

        foreach ($field_rules as $check => $check_value) {

            switch ($check) {

                case 'required':
                    if (!$field_value) {
                        $errors[$check] = isset($messages['required']) ? w($messages['required']) : self::$messages['required'];
                    }

                    break;

                case 'min_length':

                    if (mb_strlen($field_value) < $check_value) {
                        $errors[$check] = sprintf(isset($messages['min_length']) ? w($messages['min_length']) : self::$messages['min_length'], $check_value);
                    }

                    break;

                case 'max_length':

                    if (mb_strlen($field_value) > $check_value) {
                        $errors[$check] = sprintf(isset($messages['max_length']) ? w($messages['max_length']) : self::$messages['max_length'], $check_value);
                    }

                    break;

                case 'min':

                    if ($field_value < $check_value) {
                        $errors[$check] = sprintf(isset($messages['min']) ? w($messages['min']) : self::$messages['min'], $check_value);
                    }

                    break;

                case 'max':

                    if ($field_value > $check_value) {
                        $errors[$check] = sprintf(isset($messages['max']) ? w($messages['max']) : self::$messages['max'], $check_value);
                    }

                    break;

                case 'pattern':

                    if (!preg_match($check_value, $field_value)) {
                        $errors[$check] = $check_value;
                    }

                    break;

                case 'min_checkboxes_checked':

                    break;

                case 'max_checkboxes_checked':

                    break;

                case 'equal_to_field':

                    if (!isset($data[$check_value]) || $field_value != $data[$check_value]) {
                        $errors[$check] = sprintf(isset($messages['max']) ? w($messages['max']) : self::$messages['max'], $check_value);
                    }

                    break;

                // Is used for invisible fields, substituting captcha: if filled, it is invalid
                case 'empty':

                    if (!empty($field_value)) {
                        $errors[$check] = isset($messages['empty']) ? w($messages['empty']) : self::$messages['empty'];
                    }

                    break;

                case 'type':

                    switch ($check_value) {

                        case 'email':

                            if (!Verify::email($field_value)) {
                                $errors[$check] = isset($messages['type']) ? w($messages['type']) : self::$messages['email'];
                            }

                            break;

                        case 'number':

                            if (!is_numeric($field_value)) {
                                $errors[$check] = isset($messages['type']) ? w($messages['type']) : self::$messages['number'];
                            }

                            break;

                        case 'integer':

                            if (!\is_numeric($field_value) || !filter_var($field_value, FILTER_VALIDATE_INT)) {
                                $errors[$check] = isset($messages['type']) ? w($messages['type']) : self::$messages['integer'];
                            }

                            break;

                        case 'digits':

                            if (!ctype_digit($field_value)) {
                                $errors[$check] = isset($messages['type']) ? w($messages['type']) : self::$messages['digits'];
                            }

                            break;

                        case 'alphanum':

                            if (!ctype_alnum($field_value)) {
                                $errors[$check] = isset($messages['type']) ? w($messages['type']) : self::$messages['aphanum'];
                            }

                            break;

                        case 'url':

                            if (!filter_var($field_value, FILTER_VALIDATE_URL)) {
                                $errors[$check] = isset($messages['url']) ? w($messages['url']) : self::$messages['url'];
                            }

                            break;
                    }

                    break;
            }
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * @param array | string $fields
     *
     * @return array
     */
    private static function normalizeRules($fields): array
    {
        $result = [];

        foreach ((array)$fields as $k => $v) {
            if (is_numeric($k)) {
                $result[$v] = true;
            } else {
                $result[$k] = $v;
            }
        }

        return $result;
    }
}
