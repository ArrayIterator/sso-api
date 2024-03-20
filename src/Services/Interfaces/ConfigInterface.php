<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services\Interfaces;

use ArrayAccess;
use Countable;

interface ConfigInterface extends ArrayAccess, Countable
{
    /**
     * Get the name of the configuration.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Get the value of the given key.
     *
     * @param string $key
     * @param $default
     */
    public function get(string $key, $default = null);

    /**
     * Determine if the given key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Set the value at the given key.
     *
     * @param string $key
     * @param $value
     * @return void
     */
    public function set(string $key, $value): void;

    /**
     * Remove the value at the given key.
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Get all the configuration items.
     *
     * @return array
     */
    public function all(): array;
}
