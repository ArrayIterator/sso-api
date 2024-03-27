<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Symfony\Component\Yaml\Yaml;
use Traversable;
use function is_array;

final class Config implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array<string|int|float, mixed>
     */
    private array $config = [];

    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->setConfig($key, $value);
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function nestedValue(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (is_array($val)) {
                    $value[$key] = $this->nestedValue($val);
                    continue;
                }
                $value[$key] = $val;
            }

            $value = new Config($value);
        }
        return $value;
    }

    protected function setConfig(string|int|float $name, $value): void
    {
        $this->config[$name] = $this->nestedValue($value);
    }

    public function get(string|int|float $name, $default = null): mixed
    {
        if (isset($this->config[$name])) {
            return $this->config[$name];
        }
        return $default;
    }

    public function all() : array
    {
        return $this->config;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setConfig($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        // pass
    }

    public function count(): int
    {
        return count($this->all());
    }

    public static function fromYamlFile(string $fileName) : self
    {
        return new self(Yaml::parseFile($fileName));
    }
}
