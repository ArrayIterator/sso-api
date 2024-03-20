<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services\Interfaces;

use ArrayAccess;

interface ConfigCollectionInterface extends ArrayAccess
{
    /**
     * Add a new configuration, if config exists ignore it.
     *
     * @param ConfigInterface $config
     */
    public function add(ConfigInterface $config);

    /**
     * Replace the configuration with the given name.
     *
     * @param ConfigInterface $config
     */
    public function replace(ConfigInterface $config);

    /**
     * Remove the configuration with the given name.
     *
     * @param string $name
     * @see ConfigInterface::getName()
     */
    public function remove(string $name);

    /**
     * Get the configuration for the given name.
     *
     * @param string $name
     * @return ConfigInterface
     * @see ConfigInterface::getName()
     */
    public function get(string $name): ConfigInterface;

    /**
     * Determine if the given configuration exists.
     *
     * @param string $name
     * @return bool
     * @see ConfigInterface::getName()
     */
    public function has(string $name): bool;

    /**
     * Get all the configuration items.
     *
     * @return array<string, ConfigInterface>
     */
    public function all(): array;
}
