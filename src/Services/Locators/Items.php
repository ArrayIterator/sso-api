<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services\Locators;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;
use function in_array;
use function is_string;

final class Items implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var array<string, Item>
     */
    protected array $items = [];

    /**
     * @var array<string, bool>
     */
    protected array $locked = [];

    protected ?string $lastKey = null;

    public function __construct()
    {
    }

    /**
     * Check if item exists
     *
     * @param Item|string $item
     * @return bool
     */
    public function has(Item|string $item): bool
    {
        if ($item instanceof Item) {
            return isset($this->items[$item->key]) &&$this->contains($item);
        }

        return isset($this->items[$item]);
    }

    /**
     * Check if item exists
     *
     * @param Item $item
     * @return bool
     */
    public function contains(Item $item): bool
    {
        return in_array($item, $this->items, true);
    }

    /**
     * Search item by value
     *
     * @param mixed $value
     * @return Item|null
     */
    public function search(mixed $value): ?Item
    {
        foreach ($this->items as $item) {
            if ($item->value === $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Check if item is locked
     *
     * @param string $item
     * @return bool
     */
    public function isLocked(string $item): bool
    {
        return isset($this->locked[$item]);
    }

    /**
     * Lock item
     *
     * @param ?string $key
     * @return bool
     */
    public function lock(?string $key = null): bool
    {
        $key ??= $this->lastKey;
        if ($key === null) {
            return false;
        }
        if (isset($this->items[$key])) {
            $this->locked[$key] = true;
            return true;
        }
        return false;
    }

    /**
     * Add item
     *
     * @param Item $item
     * @return ?Item
     */
    public function addItem(Item $item): ?Item
    {
        if (isset($this->locked[$item->key])) {
            return null;
        }
        $this->lastKey = $item->key;
        $this->items[$item->key] = $item;
        return $item;
    }

    /**
     * Add item
     *
     * @param string $key
     * @param mixed $value
     * @return ?Item
     */
    public function add(string $key, mixed $value): ?Item
    {
        $item = new Item($key, $value);
        return $this->addItem($item);
    }

    /**
     * Get item
     *
     * @param string $key
     * @return Item|null
     */
    public function get(string $key) : ?Item
    {
        return $this->items[$key] ?? null;
    }

    /**
     * Add item and lock it
     *
     * @param Item $item
     * @return ?Item
     */
    public function addLock(Item $item): ?Item
    {
        if ($this->addItem($item)) {
            $this->lock($item->key);
            return $item;
        }
        return null;
    }

    /**
     * Remove item
     *
     * @param string $key
     * @return ?Item null if not exists, removed Item if exists
     */
    public function remove(string $key): ?Item
    {
        if ($this->isLocked($key) || !$this->has($key)) {
            return null;
        }
        $item = $this->items[$key]??null;
        if ($key === $this->lastKey) {
            $this->lastKey = null;
        }
        unset($this->items[$key]);
        return $item;
    }

    /**
     * @return array<string, Item>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return Traversable<Item>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getItems());
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->getItems());
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->has($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): ?Item
    {
        if (is_string($offset)) {
            return $this->get($offset);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            return;
        }
        $this->add($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            $this->remove($offset);
        }
    }
}
