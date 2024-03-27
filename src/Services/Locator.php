<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services;

use Pentagonal\Sso\Core\Services\Locators\Item;
use Pentagonal\Sso\Core\Services\Locators\Items;

/**
 * Global storage locator
 */
final class Locator
{
    public const DEFAULT_NAME = 'default';

    /**
     * @var array<string, Items>
     */
    private array $locatorItems = [];

    /**
     * @var array<string, bool>
     */
    private array $locked = [];

    private static Locator $instance;

    private string $name;

    private function __construct()
    {
        $this->name = self::DEFAULT_NAME;
        $this->locatorItems[self::DEFAULT_NAME] = new Items();
    }

    /**
     * @return Locator
     */
    protected static function instanceLocator(): Locator
    {
        return self::$instance ??= new self();
    }

    /**
     * @return string
     */
    public static function name() : string
    {
        return self::instanceLocator()->name;
    }

    /**
     * Get current locator
     *
     * @return Items
     */
    public static function current(): Items
    {
        $instance = self::instanceLocator();
        return $instance->locatorItems[$instance->name];
    }

    /**
     * Switch to another locator
     *
     * @param string $name
     * @return self
     */
    public static function switch(string $name): self
    {
        $instance = self::instanceLocator();
        if (! isset($instance->locatorItems[$name])) {
            $instance->locatorItems[$name] = new Items();
        }
        $instance->name = $name;
        return $instance;
    }

    /**
     * Lock items locator
     *
     * @param ?string $app
     * @return bool
     */
    public static function lock(?string $app = null): bool
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        if (! isset($instance->locatorItems[$app])) {
            return false;
        }
        $instance->locked[$app] = true;
        return true;
    }

    /**
     * Check if items locator is locked
     *
     * @param ?string $app
     * @return bool
     */
    public static function locked(?string $app = null): bool
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        return isset($instance->locked[$app]);
    }

    /**
     * Unlock items locator
     *
     * @param string $key
     * @param ?string $app
     * @return bool
     */
    public static function lockItem(string $key, ?string $app = null): bool
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        if (! isset($instance->locatorItems[$app])) {
            return false;
        }
        return $instance->locatorItems[$app]->lock($key);
    }

    /**
     * Remove value from locator
     *
     * @param string $key
     * @param ?string $app
     * @return ?Item return null if failed
     */
    public static function removeItem(string $key, ?string $app = null): ?Item
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        if (! isset($instance->locatorItems[$app])) {
            return null;
        }
        return $instance->locatorItems[$app]->remove($key);
    }

    /**
     * Set value to locator
     *
     * @param string $key
     * @param mixed $value
     * @param ?string $app
     * @return ?Item return null if failed
     */
    public static function setItem(string $key, mixed $value, ?string $app = null): ?Item
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        if (!isset($instance->locatorItems[$app])) {
            $instance->locatorItems[$app] = new Items();
        }
        return $instance->locatorItems[$app]->add($key, $value);
    }

    /**
     * Check if key exists
     *
     * @param string $key
     * @param ?string $app
     * @return bool
     */
    public static function hasItem(string $key, ?string $app = null): bool
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        return $instance->locatorItems[$app]?->has($key) ?? false;
    }

    /**
     * Get value from locator
     *
     * @param string $key
     * @param ?string $app
     * @return mixed|null
     */
    public static function getItem(string $key, ?string $app = null) : ?Item
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        return $instance->locatorItems[$app]?->get($key);
    }

    /**
     * Delete items locator
     *
     * @param string $name
     * @return ?Items
     */
    public static function remove(string $name): ?Items
    {
        $instance = self::instanceLocator();
        if (! isset($instance->locatorItems[$name])) {
            return null;
        }
        if (isset($instance->locked[$name])) {
            return null;
        }
        // prevent delete current locator
        if ($name === $instance->name) {
            return null;
        }
        $data = $instance->locatorItems[$name];
        unset($instance->locatorItems[$name]);
        return $data;
    }

    /**
     * Set items locator
     *
     * @param string $appName
     * @param Items $items
     * @return bool
     */
    public function set(string $appName, Items $items): bool
    {
        if (isset($this->locked[$appName])) {
            return false;
        }

        $this->locatorItems[$appName] = $items;
        return true;
    }

    /**
     * Get all values from locator
     *
     * @param ?string $app
     * @return ?Items
     */
    public static function get(?string $app = null) : ?Items
    {
        $instance = self::instanceLocator();
        $app ??= $instance->name;
        return $instance->locatorItems[$app] ?? null;
    }

    /**
     * Get all values from locator
     *
     * @return array<string, Items>
     */
    public static function items() : array
    {
        return self::instanceLocator()->locatorItems;
    }
}
