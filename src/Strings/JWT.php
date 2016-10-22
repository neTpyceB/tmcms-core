<?php

namespace TMCms\Strings;

use DomainException;
use UnexpectedValueException;

/**
 * JSON Web Token implementation.
 */
class JWT
{
    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string $jwt The JWT
     * @param string|null $key The secret key
     * @param bool $verify Don't skip verification process
     * @return object The JWT's payload as a PHP object
     * @throws DomainException
     * @throws UnexpectedValueException
     */
    public static function decode($jwt, $key = null, $verify = true)
    {
        $segments = explode('.', $jwt);

        if (count($segments) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }

        list($headb64, $bodyb64, $cryptob64) = $segments;

        if (($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64))) === null) {
            throw new UnexpectedValueException('Invalid segment encoding');
        }

        if (($payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64))) === null) {
            throw new UnexpectedValueException('Invalid segment encoding');
        }

        $signature = JWT::urlsafeB64Decode($cryptob64);

        if ($verify) {
            if (empty($header->alg)) {
                throw new DomainException('Empty algorithm');
            }

            if ($signature != JWT::sign("$headb64.$bodyb64", $key, $header->alg)) {
                throw new UnexpectedValueException('Signature verification failed');
            }
        }

        return $payload;
    }

    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array $payload   PHP object or array
     * @param string       $key       The secret key
     * @param string       $algorithm The signing algorithm. Supported algorithms are 'HS256', 'HS384' and 'HS512'
     *
     * @return string A signed JWT
     */
    public static function encode($payload, $key, $algorithm = 'HS256')
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $algorithm
        ];

        $segments = [];
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($header));
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($payload));

        $signing_input = implode('.', $segments);

        $signature = JWT::sign($signing_input, $key, $algorithm);
        $segments[] = JWT::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $message The message to sign
     * @param string $key     The secret key
     * @param string $method  The signing algorithm. Supported algorithms are 'HS256', 'HS384' and 'HS512'
     *
     * @return string An encrypted message
     */
    public static function sign($message, $key, $method = 'HS256')
    {
        $methods = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512'
        ];

        if (empty($methods[$method])) {
            throw new DomainException('Algorithm not supported');
        }

        return hash_hmac($methods[$method], $message, $key, true);
    }

    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return object Object representation of JSON string
     */
    public static function jsonDecode($input)
    {
        $object = json_decode($input);

        if ($object === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }

        return $object;
    }

    /**
     * Encode a PHP object into a JSON string.
     *
     * @param object|array $input A PHP object or array
     *
     * @return string JSON representation of the PHP object or array
     */
    public static function jsonEncode($input)
    {
        $json = json_encode($input);

        if ($json === 'null' && $input !== null) {
            throw new DomainException('Null result with non-null input');
        }

        return $json;
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;

        if ($remainder) {
            $pad_length = 4 - $remainder;
            $input .= str_repeat('=', $pad_length);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }
    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
}