<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Interfaces;

use Pentagonal\Sso\Core\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

interface CacheAdapterInterface extends CacheItemPoolInterface
{
    public function getItem($key) : CacheItem;

    public function hasItem($key) : bool;

    public function clear() : bool;

    public function getItems(array $keys = []) : iterable;

    public function deleteItem($key) : bool;

    public function deleteItems(array $keys) : bool;

    public function save(CacheItemInterface $item) : bool;

    public function saveDeferred(CacheItemInterface $item) : bool;

    public function commit() : bool;
}
