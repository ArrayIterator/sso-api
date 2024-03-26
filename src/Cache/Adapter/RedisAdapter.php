<?php
/** @noinspection PhpComposerExtensionStubsInspection */
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Adapter;

use DateTimeImmutable;
use Pentagonal\Sso\Core\Cache\Abstracts\AbstractAdapter;
use Pentagonal\Sso\Core\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;
use Redis;
use Throwable;
use function array_key_exists;
use function is_array;
use function is_bool;
use function time;

class RedisAdapter extends AbstractAdapter
{
    /**
     * @var int Maximum number of stored items, reduce memory usage to 100 items
     */
    public const MAX_STORED_ITEMS = 100;

    protected Redis $redis;

    /**
     * @var array<string, CacheItem>
     */
    protected array $cache = [];

    /**
     * @var array<string, true>
     */
    private array $deferredHit = [];

    public function __construct(
        Redis $redis,
        string $namespace = ''
    ) {
        parent::__construct($namespace);
        $this->redis = $redis;
    }

    /**
     * @throws \RedisException
     * @throws \Exception
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    protected function doGetItem($key): ?CacheItem
    {
        $namespace = $this->getNamespace();
        $cacheKey = CacheItem::validateKey($key);
        $cacheKey = $namespace . ':' . $cacheKey;
        if (isset($this->cache[$cacheKey])) {
            $isHit = $this->cache[$cacheKey]->isHit();
            if ($isHit && isset($this->deferredHit[$cacheKey])) {
                unset($this->deferredHit[$cacheKey]);
                return clone $this->cache[$cacheKey];
            }
            $this->saveDeferredHit();
            if ($isHit) {
                return clone $this->cache[$cacheKey];
            }
            $this->deferredHit[$cacheKey] = true;
            return $this->setHit(clone $this->cache[$cacheKey]);
        }
        $value = $this->redis->get($cacheKey);
        if ($value === false) {
            return null;
        }

        $time = time();
        if (!is_array($value)
            || !array_key_exists('value', $value)
            || !is_int($value['expiration'])
            || !is_bool($value['hit'])
            || $cacheKey !== ($value['key']??null)
            || ($value['expiration'] !== 0 && $value['expiration'] < $time)
        ) {
            $this->redis->del($cacheKey);
            return null;
        }

        if (!$value['hit']) {
            $this->deferredHit[$cacheKey] = true;
        }
        $expiresAt = new DateTimeImmutable('@' . $value['expiration']);
        $item = $this->createItem(
            $cacheKey,
            $value['value'],
            $value['hit'],
            $expiresAt
        );
        $this->cache[$cacheKey] = $item;
        return clone $item;
    }

    /**
     * @throws \RedisException
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    protected function clearItems(...$keys): bool
    {
        $namespace = $this->getNamespace();
        if (empty($keys)) {
            $this->deferredHit = [];
            $this->cache = [];
            $this->redis->del($namespace . ':*');
            return true;
        }

        $needToDelete = [];
        foreach ($keys as $key) {
            if (!is_string($key) || !$key) {
                continue;
            }
            try {
                $key = CacheItem::validateKey($key);
            } catch (Throwable) {
                continue;
            }
            $cacheKey = $namespace . ':' . $key;
            $needToDelete[] = $cacheKey;
            unset($this->deferredHit[$cacheKey], $this->cache[$cacheKey]);
        }

        if (empty($needToDelete)) {
            return true;
        }
        $this->redis->del(...$needToDelete);
        return true;
    }

    /**
     * @throws \RedisException
     */
    protected function doSaveItem(CacheItemInterface $item): bool
    {
        $namespace = $this->getNamespace();
        $key   = $item->getKey();
        $value = $item->get();
        $expiration = $item->getExpiration();
        $time = time();
        $expirationTime = $expiration ? $expiration->getTimestamp() : 0;
        if ($expirationTime !== 0 && $expirationTime < $time) {
            return true;
        }
        $cacheKey = $namespace . ':' . $key;
        $this->redis->set($cacheKey, [
            'key' => $key,
            'value' => $value,
            'expiration' => $expirationTime,
        ]);
        if ($expirationTime > $time) {
            $this->redis->expireAt($cacheKey, $expirationTime);
        }
        $this->cache[$cacheKey] = $item;
        $this->saveDeferredHit();
        while (count($this->cache) >= self::MAX_STORED_ITEMS) {
            array_shift($this->cache);
        }
        return true;
    }

    protected function saveDeferredHit(): void
    {
        $namespace = $this->getNamespace();
        try {
            foreach ($this->deferredHit as $key => $value) {
                $item = $this->cache[$key] ?? null;
                if (!$item) {
                    unset($this->deferredHit[$key]);
                    continue;
                }
                $cacheKey = $namespace . ':' . $key;
                $exp = $item->getExpiration();
                if ($exp !== null && $exp > 0 && $exp < time()) {
                    unset(
                        $this->deferredHit[$key],
                    );
                    $this->redis->del($cacheKey);
                    continue;
                }
                $this->redis->set($cacheKey, [
                    'key' => $cacheKey,
                    'value' => $item,
                    'expiration' => $exp??0,
                    'hit' => true,
                ]);
                if ($exp) {
                    $this->redis->expireAt($cacheKey, $exp->getTimestamp());
                }
                unset($this->deferredHit[$key]);
            }
        } catch (Throwable) {
            // ignore
        }
    }

    public function __destruct()
    {
        $this->saveDeferredHit();
    }
}
