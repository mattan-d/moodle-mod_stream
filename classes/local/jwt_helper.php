<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JWT.
 *
 * @package   mod_stream
 * @category  admin
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stream\local;

use Exception;

/**
 * JWT (JSON Web Token) encoding and decoding utility.
 *
 * This class provides methods for encoding and decoding JSON Web Tokens (JWT).
 *
 * @package   mod_stream
 * @category  admin
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jwt_helper {

    /**
     * Supported signing algorithms and their corresponding hashing methods.
     *
     * @var array
     */
    private static $supportedalgs = [
            'HS256' => ['hash_hmac', 'SHA256'],
            'HS512' => ['hash_hmac', 'SHA512'],
            'HS384' => ['hash_hmac', 'SHA384'],
    ];

    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param string $tokenexterno The external token for encryption
     * @param object|array $payload PHP object or array to be encoded into the JWT payload
     * @param int $expires Expiration time in seconds (default: 300)
     * @param string $alg The signing algorithm (default: 'HS256')
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512'
     * @param array|null $head An array with additional header elements to include in the JWT (default: null)
     *
     * @return string A signed JWT
     */
    public static function encode($tokenexterno, $payload, $expires = 300, $alg = 'HS256', $head = null) {
        $key = $tokenexterno;

        $header = ['typ' => 'JWT', 'alg' => $alg];
        if (isset($head) && is_array($head)) {
            $header = array_merge($head, $header);
        }

        if ($expires) {
            $payload['iat'] = time();
            $payload['nbf'] = time();
            $payload['exp'] = time() + $expires;
        }

        $segments = [];
        $segments[] = self::urlsafe(json_encode($header));
        $segments[] = self::urlsafe(json_encode($payload));
        $signinginput = implode('.', $segments);

        try {
            $signature = self::sign($signinginput, $key, $alg);
        } catch (Exception $e) {
            return "";
        }
        $segments[] = self::urlsafe($signature);

        return implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg The message to sign
     * @param string|resource $key The secret key
     * @param string $alg The signing algorithm.
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512'
     *
     * @return string An encrypted message
     *
     * @throws Exception Unsupported algorithm was specified
     */
    private static function sign($msg, $key, $alg = 'HS256') {
        if (empty(self::$supportedalgs[$alg])) {
            throw new Exception('Algoritmo não suportado');
        }
        list($function, $algorithm) = self::$supportedalgs[$alg];
        switch ($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
        }

        return "";
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    private static function urlsafe($input) {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
}
