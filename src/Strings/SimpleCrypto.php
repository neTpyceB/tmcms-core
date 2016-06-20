<?php

namespace TMCms\Strings;

use Exception;

defined('INC') or exit;

/**
 * Class SimpleCrypto
 *
 * @usage
$message = 'Ready your ammunition; we attack at dawn.';
$key = ('000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f');

$encrypted = SimpleCrypto::encrypt($message, $key);
$decrypted = SimpleCrypto::decrypt($encrypted, $key);

var_dump($encrypted, $decrypted);
 */
class SimpleCrypto
{
    const SALT = 'df465y*!th1-rhgrshdff';
    const PREFIX = 'encrypt_';

    public static function encrypt($string, $key) {
        $key = self::SALT . $key;
        return SimpleCrypto::PREFIX . strrev(base64_encode($string . $key));
    }

    public static function decrypt($string, $key) {
        $key = self::SALT . $key;
        return mb_substr(base64_decode(strrev(mb_substr($string, mb_strlen(self::PREFIX)))), 0, -mb_strlen($key));
    }
}