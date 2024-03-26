<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Adapter;

use Pentagonal\Sso\Core\Cache\Abstracts\AbstractAdapter;
use Pentagonal\Sso\Core\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;
use function is_array;
use function is_string;

class ArrayAdapter extends AbstractAdapter
{
    public const MAX_STORED_ITEMS = 1000;

    /**
     * @var array<string, array<string, CacheItem>>
     */
    private static array $caches = [];

    protected function doGetItem($key): ?CacheItem
    {
        $key = CacheItem::validateKey($key);
        $namespace = $this->getNamespace();
        if (!is_array(static::$caches[$namespace]??null)) {
            return null;
        }
        if (isset(static::$caches[$namespace][$key])) {
            if (static::$caches[$namespace][$key]->isHit()) {
                return clone static::$caches[$namespace][$key];
            }
            return $this->setHit(
                clone static::$caches[$namespace][$key]
            );
        }
        return null;
    }

    protected function clearItems(...$keys): bool
    {
        $namespace = $this->getNamespace();
        if (empty($keys)) {
            unset(static::$caches[$namespace]);
            return true;
        }
        if (!is_array(static::$caches[$namespace]??null)) {
            return true;
        }
        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }
            unset(static::$caches[$namespace][$key]);
        }
        return true;
    }

    protected function doSaveItem(CacheItemInterface $item): bool
    {
        $namespace = $this->getNamespace();
        static::$caches[$namespace] ??= [];
        while (count(static::$caches[$namespace]) >= self::MAX_STORED_ITEMS) {
            array_shift(static::$caches[$namespace]);
        }
        static::$caches[$namespace][$item->getKey()] = $item;
        return true;
    }
}
