<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Adapter;

use DateTimeImmutable;
use ErrorException;
use Exception;
use Generator;
use Pentagonal\Sso\Core\Cache\Abstracts\AbstractAdapter;
use Pentagonal\Sso\Core\Cache\CacheItem;
use Pentagonal\Sso\Core\Cache\Exceptions\InvalidArgumentException;
use Pentagonal\Sso\Core\Utils\Encryption\SimpleOpenSSL;
use Pentagonal\Sso\Core\Utils\Generator\Random;
use Pentagonal\Sso\Core\Utils\Helper\DataType;
use Psr\Cache\CacheItemInterface;
use Throwable;
use function array_shift;
use function base64_encode;
use function fclose;
use function feof;
use function fgets;
use function file_exists;
use function filemtime;
use function fopen;
use function fread;
use function fwrite;
use function hash;
use function hash_equals;
use function is_array;
use function is_bool;
use function is_dir;
use function is_file;
use function is_numeric;
use function is_string;
use function is_writable;
use function md5;
use function mkdir;
use function preg_match;
use function random_bytes;
use function realpath;
use function rename;
use function restore_error_handler;
use function rtrim;
use function scandir;
use function serialize;
use function set_error_handler;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtoupper;
use function substr;
use function time;
use function touch;
use function trim;
use function unlink;
use function unserialize;
use const DIRECTORY_SEPARATOR;
use const OPENSSL_RAW_DATA;
use const SCANDIR_SORT_NONE;

/**
 * Store in file using encryption of open ssl
 */
class FileAdapter extends AbstractAdapter
{
    /**
     * @var int Maximum number of stored items, reduce memory usage to 500 items
     */
    public const MAX_STORED_ITEMS = 100;

    /**
     * @var array<string, CacheItem>
     */
    protected array $cache = [];

    /**
     * @var array<string, string>
     */
    private array $files = [];

    /**
     * @var array<string, true>
     */
    protected array $deferredHit = [];

    /**
     * @var string
     */
    private string $directory;

    private ?string $tmpSuffix = null;

    private SimpleOpenSSL $simpleOpenSSL;

    private string $tempDir;

    /**
     * @param string $directory
     * @param string $namespace
     * @param string|null $secretKey default null using md5($this->directory)
     */
    public function __construct(
        string $directory,
        string $namespace = '',
        ?string $secretKey = null
    ) {
        $namespace  = $namespace ?: self::DEFAULT_NAMESPACE;
        parent::__construct($namespace);
        $this->init($directory, $namespace);
        $secretKey = $secretKey?:md5($this->directory);
        $this->simpleOpenSSL = new SimpleOpenSSL(
            $secretKey,
            SimpleOpenSSL::CIPHER_AES_128_CBC,
            options: OPENSSL_RAW_DATA
        );
    }

    private function init(string $directory, string $namespace): void
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(
                "Path must be a valid directory"
            );
        }
        if (!is_writable($directory)) {
            throw new InvalidArgumentException(
                "Path must be writable"
            );
        }
        if (!$this->isValidNamespace($namespace)) {
            throw new InvalidArgumentException(
                "Namespace must be a valid identifier"
            );
        }
        $directory = realpath($directory);
        if (isset($namespace[0])) {
            $directory .= DIRECTORY_SEPARATOR . $namespace;
        } else {
            $directory .= DIRECTORY_SEPARATOR . '@';
        }
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }
        if ('\\' === DIRECTORY_SEPARATOR && strlen($directory) > 234) {
            throw new InvalidArgumentException(
                "Directory path is too long"
            );
        }
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $prefix = '@' . DIRECTORY_SEPARATOR;
        $this->tempDir = $this->directory . $prefix;
    }

    final protected function isValidNamespace(string $namespace): bool
    {
        return parent::isValidNamespace($namespace);
    }

    private function scanHashDir(string $directory): Generator
    {
        if (!is_dir($directory)) {
            return;
        }

        $chars = '+-ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        for ($i = 0; $i < 38; ++$i) {
            if (!is_dir($directory.$chars[$i])) {
                continue;
            }

            for ($j = 0; $j < 38; ++$j) {
                if (!is_dir($dir = $directory.$chars[$i]. DIRECTORY_SEPARATOR .$chars[$j])) {
                    continue;
                }

                foreach (@scandir($dir, SCANDIR_SORT_NONE) ?: [] as $file) {
                    if ('.' !== $file && '..' !== $file) {
                        yield $dir. DIRECTORY_SEPARATOR .$file;
                    }
                }
            }
        }
    }

    /**
     * @param string $id
     * @param bool $mkdir true, want to create directory
     * @param string|null $directory custom directory
     * @return string file path
     */
    private function getFile(string $id, bool $mkdir = false, ?string $directory = null): string
    {
        // Use xxh128 to favor speed over security, which is not an issue here
        $hash = str_replace('/', '-', base64_encode(hash('xxh128', static::class.$id, true)));
        $dir = ($directory ?? $this->directory).strtoupper($hash[0]. DIRECTORY_SEPARATOR.$hash[1]. DIRECTORY_SEPARATOR);

        if ($mkdir && !is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        return $dir.substr($hash, 2, 20);
    }

    private function write(string $file, string $data, ?int $expiresAt = null): bool
    {
        $unlink = false;
        /**
         * @throws ErrorException
         */
        set_error_handler(
            static fn (
                $type,
                $message,
                $file,
                $line
            ) => throw new ErrorException($message, 0, $type, $file, $line)
        );
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0777, true);
        }

        try {
            $this->tmpSuffix ??= str_replace('/', '-', base64_encode(Random::bytes(6)));
            $tmp = $this->tempDir . $this->tmpSuffix;
            try {
                $h = fopen($tmp, 'x');
            } catch (ErrorException $e) {
                if (!str_contains($e->getMessage(), 'File exists')) {
                    throw $e;
                }
                $this->tmpSuffix = $prefix . str_replace('/', '-', base64_encode(random_bytes(6)));
                $tmp = $this->tempDir . $this->tmpSuffix;
                $h = fopen($tmp, 'x');
            }
            $exp = $expiresAt ?: 0;
            $ivLength = $this->simpleOpenSSL->getIvLength();
            $count = 0;
            do {
                $iv = SimpleOpenSSL::generateIv($ivLength);
            } while ($count++ < 30 && str_contains($iv, "\n"));
            if ($count >= 30) {
                $iv = Random::char($ivLength);
            }
            $data = $this->simpleOpenSSL->withIv($iv)->encrypt($data);
            $hash = md5($data);
            $data = $exp . "\n" . $hash . "\n" . $iv . $data;
            fwrite($h, $data);
            fclose($h);
            $unlink = true;

            if (null !== $expiresAt) {
                touch($tmp, $expiresAt ?: time() + 31556952); // 1 year in seconds
            }

            $success = rename($tmp, $file);
            $unlink = !$success;
            $this->tmpSuffix = null;
            return $success;
        } finally {
            restore_error_handler();
            if ($unlink && isset($tmp) && is_file($tmp)) {
                @unlink($tmp);
                $this->tmpSuffix = null;
            }
        }
    }

    /**
     * @param string $file
     * @param string $key
     * @return CacheItem|null
     */
    private function readFile(string $file, string $key): ?CacheItem
    {
        if (($fp = fopen($file, 'r')) === false) {
            return null;
        }
        $unlink = true;
        set_error_handler(function () use (&$unlink) {
            $unlink = true;
        });
        try {
            $expire = fgets($fp);
            if (!is_string($expire)) {
                return null;
            }
            $expire = str_ends_with($expire, "\n")
                ? rtrim($expire, "\n")
                : $expire;
            if (!is_numeric($expire) || str_contains($expire, '.')) {
                return null;
            }
            $expire = (int) $expire;
            if ($expire !== 0 && $expire < time()) {
                return null;
            }
            $hash = fgets($fp);
            if (!is_string($hash) || !preg_match('/^[a-f0-9]{32}\n$/', $hash)) {
                return null;
            }
            $hash = rtrim($hash, "\n");
            $unlink = false;
            $data = '';
            while (!feof($fp)) {
                $data .= fread($fp, 8192);
            }
            $data = trim($data);
            $iv = substr($data, 0, $this->simpleOpenSSL->getIvLength());
            $data = substr($data, $this->simpleOpenSSL->getIvLength());
            $dataHash = md5($data);
            $data = $this->simpleOpenSSL->decrypt($data, $iv);
            if (!hash_equals($dataHash, $hash)) {
                $unlink = true;
                return null;
            }
            if (!DataType::isSerialized($data)) {
                $unlink = true;
                return null;
            }

            $data = unserialize($data);
            if (!is_array($data)
                || !isset($data['value'], $data['isHit'], $data['key'])
                || $data['key'] !== $key
                || !is_bool($data['isHit'])
            ) {
                $unlink = true;
                return null;
            }
            return $this->createItem(
                $key,
                $data['value'],
                $data['isHit'],
                $expire === 0 ? null : new DateTimeImmutable('@' . $expire)
            );
        } catch (Exception $e) {
            $unlink = true;
        } finally {
            restore_error_handler();
            fclose($fp);
            if ($unlink) {
                 @unlink($file);
            }
        }
        return null;
    }

    protected function doGetItem($key): ?CacheItem
    {
        $key = CacheItem::validateKey($key);
        if (isset($this->cache[$key])) {
            if (isset($this->deferredHit[$key])
                && $this->cache[$key]->isHit()
            ) {
                unset($this->deferredHit[$key]);
            } else {
                $this->deferredHit[$key] = true;
            }
            return $this->setHit(
                clone $this->cache[$key]
            );
        }
        $file = $this->getFile($key);
        if (($this->files[$key] ?? null) === false) {
            return null;
        }
        $this->files[$key] = is_file($file);
        if (!$this->files[$key]) {
            return null;
        }

        $this->saveDeferredHit();
        while (count($this->cache) >= self::MAX_STORED_ITEMS) {
            array_shift($this->cache);
        }
        $cache = $this->readFile($file, $key);
        if (!$cache) {
            return null;
        }
        if (!$cache->isHit()) {
            $this->deferredHit[$key] = true;
        }
        return $this->cache[$key] = $cache;
    }

    /**
     * Prune all cache
     *
     * @return bool
     */
    public function prune(): bool
    {
        $time = time();
        $pruned = true;
        foreach ($this->scanHashDir($this->directory) as $file) {
            if (!is_file($file)) {
                continue;
            }
            if (str_starts_with($file, $this->tempDir . DIRECTORY_SEPARATOR)) {
                $pruned = (@unlink($file) || file_exists($file)) && $pruned;
                continue;
            }
            if (($fp = @fopen($file, 'r')) === false) {
                continue;
            }
            $expiresAt = fgets($fp);
            $expiresAt = rtrim($expiresAt, "\n");
            $expiresAt = is_numeric($expiresAt) ? (int) $expiresAt : null;
            if ($expiresAt === null && $time >= $expiresAt) {
                $pruned = (@unlink($file) || file_exists($file)) && $pruned;
                unset($this->files[$file]);
            }
            fclose($fp);
        }

        return $pruned;
    }

    protected function clearItems(...$keys): bool
    {
        $pruned = true;
        if (empty($keys)) {
            $this->cache = [];
            $time = time();
            foreach ($this->scanHashDir($this->directory) as $file) {
                if (str_starts_with($file, $this->tempDir . DIRECTORY_SEPARATOR)) {
                    if (filemtime($file) < ($time - 3600)) {
                        $pruned = @unlink($file) && $pruned;
                    }
                    continue;
                }
                if (is_file($file)) {
                    unset($this->files[$file]);
                    $pruned = @unlink($file) && $pruned;
                }
            }
            return $pruned;
        }

        foreach ($keys as $key) {
            if (!is_string($key) || !$key) {
                continue;
            }
            try {
                $key = CacheItem::validateKey($key);
            } catch (Throwable) {
                continue;
            }
            $file = $this->getFile($key);
            $this->files[$file] = false;
            if (is_file($file)) {
                $pruned = @unlink($file) && $pruned;
            }
            unset($this->cache[$key]);
        }
        return $pruned;
    }

    protected function doSaveItem(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->cache[$key] = $item;
        $this->deferredHit[$key] = true;
        $file = $this->getFile($key, true);
        return $this->write(
            $file,
            serialize([
                'value' => $item->get(),
                'isHit' => $item->isHit(),
                'key' => $key,
            ]),
            $item->getExpiration()?->getTimestamp()
        );
    }

    protected function saveDeferredHit(): void
    {
        try {
            foreach ($this->deferredHit as $key => $value) {
                $item = $this->cache[$key] ?? null;
                if (!$item) {
                    unset($this->deferredHit[$key]);
                    continue;
                }
                $exp = $item->getExpiration();
                if ($exp !== null && $exp > 0 && $exp < time()) {
                    unset($this->deferredHit[$key]);
                    $file = $this->getFile($key);
                    unset($this->cache[$key]);
                    if (is_file($file)) {
                        @unlink($file);
                    }
                    continue;
                }
                $this->write(
                    $this->getFile($key, true),
                    serialize([
                        'value' => $item->get(),
                        'isHit' => true,
                        'key' => $key,
                    ]),
                    $exp?->getTimestamp()
                );

                unset($this->deferredHit[$key]);
            }
        } catch (Throwable) {
            // ignore
        }
    }

    public function __destruct()
    {
        $this->saveDeferredHit();
        if ($this->tmpSuffix && is_file($this->tempDir . $this->tmpSuffix)) {
            @unlink($this->tempDir . $this->tmpSuffix);
            $this->tmpSuffix = null;
        }
    }
}
