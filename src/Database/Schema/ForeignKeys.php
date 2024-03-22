<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use function count;
use function strtolower;

class ForeignKeys implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var array<string, ForeignKey>
     */
    protected array $foreignKeys = [];

    public function __construct(ForeignKey ...$foreignKeys)
    {
        foreach ($foreignKeys as $foreignKey) {
            $this->add($foreignKey);
        }
    }

    public function has(string|ForeignKey $foreignKeyName) : bool
    {
        return isset($this->foreignKeys[strtolower((string) $foreignKeyName)]);
    }

    /**
     * Add Table
     *
     * @param ForeignKey $foreignKey
     * @return $this
     */
    public function add(ForeignKey $foreignKey) : static
    {
        $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
        return $this;
    }

    /**
     * Get ForeignKey
     *
     * @param string $foreignKey
     * @return ?ForeignKey
     */
    public function get(string $foreignKey) : ?ForeignKey
    {
        return $this->foreignKeys[$foreignKey] ?? null;
    }

    /**
     * Remove Table
     *
     * @param string|ForeignKey $foreignKey
     * @return ?ForeignKey
     */
    public function remove(string|ForeignKey $foreignKey) : ?ForeignKey
    {
        $foreignKey = strtolower((string) $foreignKey);
        if (isset($this->foreignKeys[$foreignKey])) {
            $table = $this->foreignKeys[$foreignKey];
            unset($this->foreignKeys[$foreignKey]);
            return $table;
        }
        return null;
    }

    /**
     * @return array<string, ForeignKey>
     */
    public function all() : array
    {
        return $this->foreignKeys;
    }

    /**
     * @return Traversable<string,ForeignKey>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->all());
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
