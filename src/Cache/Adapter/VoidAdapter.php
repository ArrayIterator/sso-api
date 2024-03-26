<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Adapter;

use Pentagonal\Sso\Core\Cache\Abstracts\AbstractAdapter;
use Pentagonal\Sso\Core\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;

class VoidAdapter extends AbstractAdapter
{
    public function saveDeferred(CacheItemInterface $item): bool
    {
        return true;
    }

    protected function doGetItem($key): ?CacheItem
    {
        return null;
    }

    protected function clearItems(...$keys): bool
    {
        return true;
    }

    protected function doSaveItem(CacheItemInterface $item): bool
    {
        return true;
    }
}
