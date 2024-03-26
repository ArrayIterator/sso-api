<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache\Abstracts;

use BadMethodCallException;
use DateTimeInterface;
use Pentagonal\Sso\Core\Cache\CacheItem;
use Pentagonal\Sso\Core\Cache\Exceptions\InvalidArgumentException;
use Pentagonal\Sso\Core\Cache\Interfaces\CacheAdapterInterface;
use Psr\Cache\CacheItemInterface;
use function func_num_args;

abstract class AbstractAdapter implements CacheAdapterInterface
{
    public const DEFAULT_NAMESPACE = "_";

    /**
     * @var CacheItemInterface[]
     */
    protected array $deferred = [];

    /**
     * @var string
     */
    protected string $namespace = "_";

    /**
     * @var int|null
     */
    protected ?int $defaultLifetime = null;

    public function __construct(string $namespace = '')
    {
        if ($namespace !== "" && !$this->isValidNamespace($namespace)) {
            throw new InvalidArgumentException(
                "Namespace must be a valid identifier"
            );
        }
        $this->namespace = $namespace?:self::DEFAULT_NAMESPACE;
    }

    public function getDefaultLifetime(): ?int
    {
        return $this->defaultLifetime;
    }

    public function setDefaultLifetime(?int $defaultLifetime): void
    {
        $this->defaultLifetime = $defaultLifetime;
    }

    /**
     * @param string $key
     * @param mixed|null $value
     * @param bool $isHit
     * @param DateTimeInterface|null $expiration
     * @return CacheItem
     */
    protected function createItem(
        string $key,
        mixed $value = null,
        bool $isHit = false,
        ?DateTimeInterface $expiration = null
    ) : CacheItem {
        $expiration = func_num_args() > 3
            ? $expiration
            : $this->defaultLifetime;
        $key = CacheItem::validateKey($key);
        return (function ($key, $value, $isHit, $expiration) {
            /**
             * @var CacheItem $this
             */
            $this->{"key"} = $key;
            $this->{"isHit"} = $isHit;
            $this->{"value"} = $value;
            if ($expiration instanceof DateTimeInterface) {
                $this->{"expiration"} = $expiration;
            } else {
                $this->expiresAfter($expiration);
            }
            return $this;
        })->call(new CacheItem(), $key, $value, $isHit, $expiration);
    }

    /**
     * @inheritDoc
     */
    public function getItem($key): CacheItem
    {
        return $this->doGetItem($key)?? $this->createItem($key);
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key): bool
    {
        return $this->doGetItem($key) instanceof CacheItem;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        return $this->clearItems();
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys = []): iterable
    {
        return array_map(fn ($key) => $this->getItem($key), $keys);
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key): bool
    {
        return $this->clearItems($key);
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys): bool
    {
        return $this->clearItems(...$keys);
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item): bool
    {
        return $this->doSaveItem($item);
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        $result = true;
        foreach ($this->deferred as $key => $item) {
            unset($this->deferred[$key]);
            $result = $result && $this->save($item);
        }
        $this->deferred = [];
        return $result;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    protected function isValidNamespace(string $namespace): bool
    {
        return strlen($namespace) < 128 && preg_match('/^[a-zA-Z0-9_]+$/', $namespace) === 1;
    }

    /**
     * @param CacheItem $item
     * @return CacheItem
     */
    protected function setHit(CacheItem $item): CacheItem
    {
        if ($item->isHit()) {
            return $item;
        }
        return (function ($isHit) {
            /**
             * @var CacheItem $this
             */
            $this->{"isHit"} = true;
            return $this;
        })->call($item);
    }

    abstract protected function doGetItem($key) : ?CacheItem;

    /**
     * Clear the items, if no keys provided, clear all items
     *
     * @param ...$keys
     * @return bool
     */
    abstract protected function clearItems(...$keys) : bool;

    /**
     * @param CacheItemInterface $item
     * @return bool
     */
    abstract protected function doSaveItem(CacheItemInterface $item) : bool;

    public function __sleep(): array
    {
        throw new BadMethodCallException('Cannot serialize '.__CLASS__);
    }

    public function __wakeup(): void
    {
        throw new BadMethodCallException('Cannot unserialize '.__CLASS__);
    }
}
