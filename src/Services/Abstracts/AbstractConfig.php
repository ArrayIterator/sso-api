<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services\Abstracts;

use Pentagonal\Sso\Core\Services\Interfaces\ConfigInterface;
use function strtolower;

abstract class AbstractConfig implements ConfigInterface
{
    protected array $configurations = [];

    /**
     * @var string $name configuration name
     * This name is unique and follow the rule of the configuration
     */
    protected string $name;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        if (!isset($this->name)) {
            $name = get_class($this);
            $name = substr($name, strrpos($name, '\\') + 1);
            $this->name = strtolower($name);
        }
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, $default = null)
    {
        return $this->has($key) ? $this->configurations[$key] : $default;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return isset($this->configurations[$key]);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value): void
    {
        $this->configurations[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): void
    {
        unset($this->configurations[$key]);
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->configurations;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    /**
     * @return int count of configuration
     */
    public function count() : int
    {
        return count($this->configurations);
    }
}
