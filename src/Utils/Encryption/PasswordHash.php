<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Utils\Encryption;

use Exception;
use function chr;
use function crypt;
use function max;
use function min;
use function mt_rand;
use function openssl_random_pseudo_bytes;
use function password_algos;
use function password_hash;
use function password_needs_rehash;
use function password_verify;
use function preg_match;
use function random_bytes;
use function str_contains;
use function str_starts_with;
use function strlen;
use function strtolower;
use const PASSWORD_DEFAULT;

/**
 * Portable Password Hashing Library based on OpenWall PassWord Hash.
 * Change that follows requirements.
 * This class based on static method for password_hash & open wall compatibility
 * Minimum requirements php-7.4
 * Algorithm will ignore if not supported or using portable method
 *
 *
 * #
 * ## CREATE HASHED PASSWORD
 *
 * - Portable Password always return 34 characters length,
 *   it will retry 10 times if failed to generate portable password.
 *   If not possible, it will use password_hash() method
 *
 * ```
 * $password = 'password';
 * $cost = 10;
 * $portable = false;
 * $algo = PASSWORD_DEFAULT;
 * $hash = PasswordHash::hash($password, $portable, $cost, $algo);
 * ```
 *
 * #
 * ## VERIFY PASSWORD & HASH
 *
 * - Method verify will automatically detect password hash type (portable or not)
 * - If portable, it will use crypt() method
 * - If not portable, it will use password_verify()
 *
 * ```
 * $isVerified = PasswordHash::verify($password, $hash); // boolean
 * ```
 *
 * #
 * ## CHECK IF PASSWORD NEED REHASH
 *
 * - Method will check if it was valid portable password or not, if it was invalid rule
 *   it will use password_needs_rehash() method
 * - The password need rehash also check the crypt(c) hash (2a, 2b, 2c, 2x)
 *
 * ```
 * $isNeedRehash = PasswordHash::needRehash($hash); // boolean
 * ```
 *
 * #
 * @link https://en.wikipedia.org/wiki/Crypt_(C),
 * @link http://www.openwall.com/phpass/
 * @see PasswordHash::needRehash()
 */
final class PasswordHash
{
    /**
     * Default Cost for Hashing
     * default using 10 that follow password_hash()
     */
    public const DEFAULT_COST = 10;

    /**
     * 64 Character for Encoding [0-9A-Za-z./]
     */
    public const ITO_A64 =  './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * @var ?array available password algorithms
     */
    private static ?array $passwordAlgorithms = null;

    /**
     * Create random bytes
     *
     * @param int $bytes
     * @return string
     */
    public static function randomBytes(int $bytes) : string
    {
        try {
            $random = random_bytes($bytes);
        } catch (Exception $e) {
            $random = openssl_random_pseudo_bytes($bytes);
        }
        if (empty($random)) {
            $random = '';
            while (strlen($random) < $bytes) {
                $random .= chr(mt_rand(0, 255));
            }
        }
        return $random;
    }

    /**
     * Encode 64
     *
     * @param string $input
     * @param int $count
     * @return string
     */
    protected static function encode64(string $input, int $count): string
    {
        $output = '';
        $iteration = 0;
        $maxOffset = strlen(self::ITO_A64) - 1;
        do {
            $value = ord($input[$iteration++]);
            $output .= self::ITO_A64[$value & $maxOffset];
            if ($iteration < $count) {
                $value |= ord($input[$iteration]) << 8; // 8 bit
            }
            $output .= self::ITO_A64[($value >> 6) & $maxOffset]; // 6 bit
            if ($iteration++ >= $count) {
                break;
            }
            if ($iteration < $count) {
                $value |= ord($input[$iteration]) << 16; // 16 bit
            }
            $output .= self::ITO_A64[($value >> 12) & $maxOffset]; // 12 bit
            if ($iteration++ >= $count) {
                break;
            }
            $output .= self::ITO_A64[($value >> 18) & $maxOffset]; // 18 bit
        } while ($iteration < $count);
        return $output;
    }

    /**
     * Crypt password
     *
     * @param string $password
     * @param string $setting
     * @return string
     */
    private static function crypt(string $password, string $setting): string
    {
        $output = '*0';
        if (str_starts_with($setting, $output)) {
            $output = '*1';
        }

        // see generate salt
        if (strlen($setting) < 12) {
            return $output;
        }

        // find log offset
        $countLog = strpos(self::ITO_A64, $setting[3]);
        if ($countLog < 7 || $countLog > 30) {
            return $output;
        }

        $count = 1 << $countLog;
        $salt = substr($setting, 4, 8);
        if (strlen($salt) !== 8) {
            return $output;
        }

        // create hash based of salt and password
        $hash = md5($salt . $password, true);
        // loop based on 8 bit count
        do {
            $hash = md5($hash . $password, true);
        } while (--$count);

        // output, 12 char + 22 char
        $output = substr($setting, 0, 12);
        $output .= self::encode64($hash, 16);
        return $output;
    }

    /**
     * Check if hash is portable
     *
     * @param string $hash
     * @return bool
     */
    public static function isPortable(string $hash): bool
    {
        return strlen($hash) !== 34 || preg_match('~^\$[PH]\$[0-9a-zA-Z/.]{31}$~', $hash) === 1;
    }

    /**
     * Verify password
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verify(string $password, string $hash): bool
    {
        /*
         * - Portable Hash: 34
         * - Password BCRYPT: 60
         * - Argon-2i: 95 / 96
         * - password hash should start with $
         */
        $length = strlen($hash);
        if ($length < 34 || $hash[0] !== '$') {
            return false;
        }

        // check if hash is not portable
        if ($length !== 34 || $hash[2] !== '$') {
            return !str_contains('PH', $hash[1]) && password_verify($password, $hash);
        }

        $crypt = self::crypt($password, $hash);
        $hash = str_starts_with($crypt, '*') ? crypt($password, $hash) : $hash;
        return $crypt === $hash;
    }

    /**
     * Check if password need rehash
     *
     * @param string $hash
     * @return bool
     */
    public static function needRehash(string $hash): bool
    {
        $length = strlen($hash);
        if ($length < 34 || $hash[0] !== '$') {
            return true;
        }

        // check if hash is not portable
        if ($length !== 34 || $hash[2] !== '$') {
            if (str_contains('PH', $hash[1])) {
                return true;
            }

            // we follow standard
            // 2[abxy] -> bcrypt ($2a$, $2b$, $2x$, $2y$)
            // argon2i -> argon 2 i ($argon2i$)
            // argon2id -> argon 2 id ($argon2id$)
            // argon2ds -> argon 2 ds ($argon2ds$)
            // argon2d -> argon 2 d ($argon2d$)
            return preg_match('~^\$(2[abxy]?|argon2(?:id?|ds?))\$~', $hash, $matches)
                && password_needs_rehash($hash, $matches[1]);
        }

        return ! self::isPortable($hash);
    }

    /**
     * Hash the password
     *
     * @param string $password
     * @param bool $portable
     * @param int $cost
     * @param string $algo
     * @return string
     */
    public static function hash(
        string $password,
        bool $portable = false,
        int $cost = self::DEFAULT_COST,
        string $algo = PASSWORD_DEFAULT
    ): string {

        // set cost
        $cost = max(4, min(31, $cost));
        if ($portable) {
            $maxRetry = 10;
            do {
                $output = '$P$';
                $output .= self::ITO_A64[min($cost + 5, 30)];
                $output .= self::encode64(self::randomBytes(6), 6);
                // output should 12
                $hash = self::crypt($password, $output);
                if (strlen($hash) === 34) {
                    return $hash;
                }
            } while (--$maxRetry > 0);
            // if no succeed, force to @use password_hash()
        }

        if (!self::$passwordAlgorithms) {
            self::$passwordAlgorithms = [];
            foreach (password_algos() as $algo) {
                self::$passwordAlgorithms[strtolower($algo)] = $algo;
            }
        }

        $algo = strtolower(trim($algo));
        $algo = self::$passwordAlgorithms[$algo] ?? PASSWORD_DEFAULT;
        return password_hash($password, $algo, ['cost' => $cost]);
    }
}
