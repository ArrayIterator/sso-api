<?php
/**
 * @noinspection PhpUnused
 */
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Utils\Encryption;

use Pentagonal\Sso\Core\Exceptions\InvalidArgumentException;
use function array_map;
use function get_object_vars;
use function openssl_cipher_iv_length;
use function sprintf;
use function strlen;
use const OPENSSL_RAW_DATA;

class SimpleOpenSSL
{
    public const CIPHER_AES_128_CBC = 'AES-128-CBC';
    public const CIPHER_AES_128_CBC_CTS = 'AES-128-CBC-CTS';
    public const CIPHER_AES_128_CBC_HMAC_SHA1 = 'AES-128-CBC-HMAC-SHA1';
    public const CIPHER_AES_128_CBC_HMAC_SHA256 = 'AES-128-CBC-HMAC-SHA256';
    public const CIPHER_AES_128_CCM = 'AES-128-CCM';
    public const CIPHER_AES_128_CFB = 'AES-128-CFB';
    public const CIPHER_AES_128_CFB1 = 'AES-128-CFB1';
    public const CIPHER_AES_128_CFB8 = 'AES-128-CFB8';
    public const CIPHER_AES_128_CTR = 'AES-128-CTR';
    public const CIPHER_AES_128_ECB = 'AES-128-ECB';
    public const CIPHER_AES_128_GCM = 'AES-128-GCM';
    public const CIPHER_AES_128_OCB = 'AES-128-OCB';
    public const CIPHER_AES_128_OFB = 'AES-128-OFB';
    public const CIPHER_AES_128_SIV = 'AES-128-SIV';
    public const CIPHER_AES_128_WRAP = 'AES-128-WRAP';
    public const CIPHER_AES_128_WRAP_INV = 'AES-128-WRAP-INV';
    public const CIPHER_AES_128_WRAP_PAD = 'AES-128-WRAP-PAD';
    public const CIPHER_AES_128_WRAP_PAD_INV = 'AES-128-WRAP-PAD-INV';
    public const CIPHER_AES_128_XTS = 'AES-128-XTS';
    public const CIPHER_AES_192_CBC = 'AES-192-CBC';
    public const CIPHER_AES_192_CBC_CTS = 'AES-192-CBC-CTS';
    public const CIPHER_AES_192_CCM = 'AES-192-CCM';
    public const CIPHER_AES_192_CFB = 'AES-192-CFB';
    public const CIPHER_AES_192_CFB1 = 'AES-192-CFB1';
    public const CIPHER_AES_192_CFB8 = 'AES-192-CFB8';
    public const CIPHER_AES_192_CTR = 'AES-192-CTR';
    public const CIPHER_AES_192_ECB = 'AES-192-ECB';
    public const CIPHER_AES_192_GCM = 'AES-192-GCM';
    public const CIPHER_AES_192_OCB = 'AES-192-OCB';
    public const CIPHER_AES_192_OFB = 'AES-192-OFB';
    public const CIPHER_AES_192_SIV = 'AES-192-SIV';
    public const CIPHER_AES_192_WRAP = 'AES-192-WRAP';
    public const CIPHER_AES_192_WRAP_INV = 'AES-192-WRAP-INV';
    public const CIPHER_AES_192_WRAP_PAD = 'AES-192-WRAP-PAD';
    public const CIPHER_AES_192_WRAP_PAD_INV = 'AES-192-WRAP-PAD-INV';
    public const CIPHER_AES_256_CBC = 'AES-256-CBC';
    public const CIPHER_AES_256_CBC_CTS = 'AES-256-CBC-CTS';
    public const CIPHER_AES_256_CBC_HMAC_SHA1 = 'AES-256-CBC-HMAC-SHA1';
    public const CIPHER_AES_256_CBC_HMAC_SHA256 = 'AES-256-CBC-HMAC-SHA256';
    public const CIPHER_AES_256_CCM = 'AES-256-CCM';
    public const CIPHER_AES_256_CFB = 'AES-256-CFB';
    public const CIPHER_AES_256_CFB1 = 'AES-256-CFB1';
    public const CIPHER_AES_256_CFB8 = 'AES-256-CFB8';
    public const CIPHER_AES_256_CTR = 'AES-256-CTR';
    public const CIPHER_AES_256_ECB = 'AES-256-ECB';
    public const CIPHER_AES_256_GCM = 'AES-256-GCM';
    public const CIPHER_AES_256_OCB = 'AES-256-OCB';
    public const CIPHER_AES_256_OFB = 'AES-256-OFB';
    public const CIPHER_AES_256_SIV = 'AES-256-SIV';
    public const CIPHER_AES_256_WRAP = 'AES-256-WRAP';
    public const CIPHER_AES_256_WRAP_INV = 'AES-256-WRAP-INV';
    public const CIPHER_AES_256_WRAP_PAD = 'AES-256-WRAP-PAD';
    public const CIPHER_AES_256_WRAP_PAD_INV = 'AES-256-WRAP-PAD-INV';
    public const CIPHER_AES_256_XTS = 'AES-256-XTS';
    public const CIPHER_ARIA_128_CBC = 'ARIA-128-CBC';
    public const CIPHER_ARIA_128_CCM = 'ARIA-128-CCM';
    public const CIPHER_ARIA_128_CFB = 'ARIA-128-CFB';
    public const CIPHER_ARIA_128_CFB1 = 'ARIA-128-CFB1';
    public const CIPHER_ARIA_128_CFB8 = 'ARIA-128-CFB8';
    public const CIPHER_ARIA_128_CTR = 'ARIA-128-CTR';
    public const CIPHER_ARIA_128_ECB = 'ARIA-128-ECB';
    public const CIPHER_ARIA_128_GCM = 'ARIA-128-GCM';
    public const CIPHER_ARIA_128_OFB = 'ARIA-128-OFB';
    public const CIPHER_ARIA_192_CBC = 'ARIA-192-CBC';
    public const CIPHER_ARIA_192_CCM = 'ARIA-192-CCM';
    public const CIPHER_ARIA_192_CFB = 'ARIA-192-CFB';
    public const CIPHER_ARIA_192_CFB1 = 'ARIA-192-CFB1';
    public const CIPHER_ARIA_192_CFB8 = 'ARIA-192-CFB8';
    public const CIPHER_ARIA_192_CTR = 'ARIA-192-CTR';
    public const CIPHER_ARIA_192_ECB = 'ARIA-192-ECB';
    public const CIPHER_ARIA_192_GCM = 'ARIA-192-GCM';
    public const CIPHER_ARIA_192_OFB = 'ARIA-192-OFB';
    public const CIPHER_ARIA_256_CBC = 'ARIA-256-CBC';
    public const CIPHER_ARIA_256_CCM = 'ARIA-256-CCM';
    public const CIPHER_ARIA_256_CFB = 'ARIA-256-CFB';
    public const CIPHER_ARIA_256_CFB1 = 'ARIA-256-CFB1';
    public const CIPHER_ARIA_256_CFB8 = 'ARIA-256-CFB8';
    public const CIPHER_ARIA_256_CTR = 'ARIA-256-CTR';
    public const CIPHER_ARIA_256_ECB = 'ARIA-256-ECB';
    public const CIPHER_ARIA_256_GCM = 'ARIA-256-GCM';
    public const CIPHER_ARIA_256_OFB = 'ARIA-256-OFB';
    public const CIPHER_CAMELLIA_128_CBC = 'CAMELLIA-128-CBC';
    public const CIPHER_CAMELLIA_128_CBC_CTS = 'CAMELLIA-128-CBC-CTS';
    public const CIPHER_CAMELLIA_128_CFB = 'CAMELLIA-128-CFB';
    public const CIPHER_CAMELLIA_128_CFB1 = 'CAMELLIA-128-CFB1';
    public const CIPHER_CAMELLIA_128_CFB8 = 'CAMELLIA-128-CFB8';
    public const CIPHER_CAMELLIA_128_CTR = 'CAMELLIA-128-CTR';
    public const CIPHER_CAMELLIA_128_ECB = 'CAMELLIA-128-ECB';
    public const CIPHER_CAMELLIA_128_OFB = 'CAMELLIA-128-OFB';
    public const CIPHER_CAMELLIA_192_CBC = 'CAMELLIA-192-CBC';
    public const CIPHER_CAMELLIA_192_CBC_CTS = 'CAMELLIA-192-CBC-CTS';
    public const CIPHER_CAMELLIA_192_CFB = 'CAMELLIA-192-CFB';
    public const CIPHER_CAMELLIA_192_CFB1 = 'CAMELLIA-192-CFB1';
    public const CIPHER_CAMELLIA_192_CFB8 = 'CAMELLIA-192-CFB8';
    public const CIPHER_CAMELLIA_192_CTR = 'CAMELLIA-192-CTR';
    public const CIPHER_CAMELLIA_192_ECB = 'CAMELLIA-192-ECB';
    public const CIPHER_CAMELLIA_192_OFB = 'CAMELLIA-192-OFB';
    public const CIPHER_CAMELLIA_256_CBC = 'CAMELLIA-256-CBC';
    public const CIPHER_CAMELLIA_256_CBC_CTS = 'CAMELLIA-256-CBC-CTS';
    public const CIPHER_CAMELLIA_256_CFB = 'CAMELLIA-256-CFB';
    public const CIPHER_CAMELLIA_256_CFB1 = 'CAMELLIA-256-CFB1';
    public const CIPHER_CAMELLIA_256_CFB8 = 'CAMELLIA-256-CFB8';
    public const CIPHER_CAMELLIA_256_CTR = 'CAMELLIA-256-CTR';
    public const CIPHER_CAMELLIA_256_ECB = 'CAMELLIA-256-ECB';
    public const CIPHER_CAMELLIA_256_OFB = 'CAMELLIA-256-OFB';
    public const CIPHER_CHACHA20 = 'CHACHA20';
    public const CIPHER_CHACHA20_POLY1305 = 'CHACHA20-POLY1305';
    public const CIPHER_DES_EDE_CBC = 'DES-EDE-CBC';
    public const CIPHER_DES_EDE_CFB = 'DES-EDE-CFB';
    public const CIPHER_DES_EDE_ECB = 'DES-EDE-ECB';
    public const CIPHER_DES_EDE_OFB = 'DES-EDE-OFB';
    public const CIPHER_DES_EDE3_CBC = 'DES-EDE3-CBC';
    public const CIPHER_DES_EDE3_CFB = 'DES-EDE3-CFB';
    public const CIPHER_DES_EDE3_CFB1 = 'DES-EDE3-CFB1';
    public const CIPHER_DES_EDE3_CFB8 = 'DES-EDE3-CFB8';
    public const CIPHER_DES_EDE3_ECB = 'DES-EDE3-ECB';
    public const CIPHER_DES_EDE3_OFB = 'DES-EDE3-OFB';
    public const CIPHER_DES3_WRAP = 'DES3-WRAP';
    public const CIPHER_SM4_CBC = 'SM4-CBC';
    public const CIPHER_SM4_CFB = 'SM4-CFB';
    public const CIPHER_SM4_CTR = 'SM4-CTR';
    public const CIPHER_SM4_ECB = 'SM4-ECB';
    public const CIPHER_SM4_OFB = "SM4-OFB";

    /**
     * Default Cipher, use AES-128-CBC for default
     * 128-bit key still secure enough, for faster encryption
     *
     * @var string
     */
    public const DEFAULT_CIPHER = self::CIPHER_AES_128_CBC;

    private static ?array $availableCiphers = null;

    /**
     * @var string
     */
    private string $key;

    /**
     * @var string
     */
    private string $cipher;

    /**
     * @var ?string
     */
    private ?string $iv;

    /**
     * @var int
     */
    private int $options;

    /**
     * SimpleEncryption constructor.
     *
     * @param string $key
     * @param string $cipher
     * @param ?string $iv
     * @param int $options
     */
    public function __construct(
        string $key,
        string $cipher = self::DEFAULT_CIPHER,
        ?string $iv = null,
        int $options = 0
    ) {
        if (!self::isAvailableCipher($cipher)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cipher "%s" is not available',
                    $cipher
                )
            );
        }
        $cipher = self::normalizeCipher($cipher);
        $iv = !$iv ? null : $iv;
        $this->key = $key;
        $this->iv = $iv;
        $this->options = $options;
        $this->cipher = $cipher;
    }

    /**
     * Generate IV
     *
     * @param int $length
     * @return string
     */
    public static function generateIv(int $length): string
    {
        return openssl_random_pseudo_bytes($length);
    }

    /**
     * Get IV length
     *
     * @param string $cipher
     * @return int|null
     */
    public static function ivLength(string $cipher): ?int
    {
        $cipher = self::normalizeCipher($cipher);
        return openssl_cipher_iv_length($cipher)?:null;
    }

    public static function normalizeCipher(string $cipher): string
    {
        $cipher = trim($cipher);
        return strtoupper(str_replace('_', '-', $cipher));
    }

    public static function isAvailableCipher(string $cipher): bool
    {
        $cipher = self::normalizeCipher($cipher);
        return in_array($cipher, self::getAvailableCiphers(), true);
    }

    /**
     * Get available ciphers
     *
     * @return array<string>
     */
    public static function getAvailableCiphers(): array
    {
        if (self::$availableCiphers === null) {
            self::$availableCiphers = array_map(
                'strtoupper',
                openssl_get_cipher_methods(true)
            );
        }

        return self::$availableCiphers;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCipher(): string
    {
        return $this->cipher;
    }

    public function getIv(): ?string
    {
        return $this->iv;
    }

    public function getOptions(): int
    {
        return $this->options;
    }

    public function getIvLength() : int
    {
        return self::ivLength($this->getCipher());
    }

    /**
     * Encrypt
     *
     * @param string $data
     * @return string
     */
    public function encrypt(string $data): string
    {
        $iv = $this->getIv();
        $cipher = $this->getCipher();
        $ivLength = $this->getIvLength();
        if (!$iv) {
            $iv = self::generateIv($ivLength);
            $this->iv = $iv;
        }
        if ($iv && $ivLength !== strlen($iv)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid IV length for cipher "%s". Iv length must be %d',
                    $cipher,
                    $ivLength
                )
            );
        }
        $result = openssl_encrypt(
            $data,
            $cipher,
            $this->getKey(),
            $this->getOptions(),
            $iv
        );
        if ($result === false) {
            throw new InvalidArgumentException(
                'Failed to encrypt data'
            );
        }
        return $result;
    }

    /**
     * Decrypt
     *
     * @param string $data
     * @param string|null $iv
     * @return string
     * @throws InvalidArgumentException
     */
    public function decrypt(string $data, ?string $iv = null): string
    {
        $iv ??= $this->getIv();
        if (!$iv) {
            throw new InvalidArgumentException('IV is required for decryption');
        }
        $result = openssl_decrypt(
            $data,
            $this->getCipher(),
            $this->getKey(),
            $this->getOptions(),
            $iv
        );
        if ($result === false) {
            throw new InvalidArgumentException('Invalid data');
        }
        return $result;
    }

    /**
     * @param string $key
     * @return static
     */
    public function withKey(string $key): static
    {
        $new = clone $this;
        $new->key = $key;
        return $new;
    }

    /**
     * @param string $cipher
     * @return static
     */
    public function withCipher(string $cipher): static
    {
        $new = clone $this;
        if (!self::isAvailableCipher($cipher)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cipher "%s" is not available',
                    $cipher
                )
            );
        }
        $cipher = self::normalizeCipher($cipher);
        $new->cipher = $cipher;
        if ($new->iv && self::ivLength($cipher) !== strlen($new->iv)) {
            $new->iv = null;
        }
        return $new;
    }

    /**
     * @param string|null $iv
     * @return static
     */
    public function withIv(?string $iv): static
    {
        $new = clone $this;
        $new->iv = $iv;
        $ivLength = self::ivLength($new->cipher);
        if ($new->iv && $ivLength !== strlen($new->iv)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid IV length for cipher "%s". Iv length must be %d',
                    $new->cipher,
                    $ivLength
                )
            );
        }

        return $new;
    }

    /**
     * @param int $options
     * @return static
     */
    public function withOptions(int $options): static
    {
        $new = clone $this;
        $new->options = $options;
        return $new;
    }

    /**
     * Encrypt data
     *
     * @param string $data
     * @param string $key
     * @param string|null $iv
     * @param string $cipher
     * @param int $options
     * @return array{
     *     string,
     *     string
     * }
     */
    public static function encryptData(
        string $data,
        string $key,
        ?string $iv = null,
        string $cipher = self::DEFAULT_CIPHER,
        int $options = 0
    ): array {
        $obj  = self::create($key, $cipher, $iv, $options);
        $data = $obj->encrypt($data);
        return [
            $obj->getIv(),
            $data,
        ];
    }

    /**
     * Decrypt data
     *
     * @param string $data
     * @param string $key
     * @param string $iv
     * @param string $cipher
     * @param ?int $options
     * @return string
     */
    public static function decryptData(
        string $data,
        string $key,
        string $iv,
        string $cipher = self::DEFAULT_CIPHER,
        ?int $options = null
    ): string {
        // options is null & contain binary data
        $options ??= strlen($data) > 0 && preg_match('/[^\x20-\x7E]/', $data)
            ? OPENSSL_RAW_DATA
            : 0;
        return self::create($key, $cipher, $iv, $options)->decrypt($data);
    }

    /**
     * @param string $key
     * @param string $cipher
     * @param string|null $iv
     * @param int $options
     * @return static
     */
    public static function create(
        string $key,
        string $cipher = self::DEFAULT_CIPHER,
        ?string $iv = null,
        int $options = 0
    ): static {
        return new static($key, $cipher, $iv, $options);
    }

    public function __debugInfo(): ?array
    {
        $data = get_object_vars($this);
        $data['key'] = '<redacted>';
        $data['iv'] = '<redacted>';
        return $data;
    }
}
