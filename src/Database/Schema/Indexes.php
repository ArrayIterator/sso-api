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

class Indexes implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var array<string, Index>
     */
    protected array $indexes = [];

    public function __construct(Index ...$indexes)
    {
        foreach ($indexes as $index) {
            $this->add($index);
        }
    }

    public function has(string|Index $foreignKeyName) : bool
    {
        return isset($this->indexes[strtolower((string) $foreignKeyName)]);
    }

    /**
     * Add Table
     *
     * @param Index $index
     * @return $this
     */
    public function add(Index $index) : static
    {
        $this->indexes[$index->getName()] = $index;
        return $this;
    }

    /**
     * Get ForeignKey
     *
     * @param string $index
     * @return ?Index
     */
    public function get(string $index) : ?Index
    {
        return $this->indexes[$index] ?? null;
    }

    /**
     * Remove Table
     *
     * @param string|Index $index
     * @return ?Index
     */
    public function remove(string|Index $index) : ?Index
    {
        $index = strtolower((string) $index);
        if (isset($this->indexes[$index])) {
            $table = $this->indexes[$index];
            unset($this->indexes[$index]);
            return $table;
        }
        return null;
    }

    /**
     * @return array<string, Index>
     */
    public function all() : array
    {
        return $this->indexes;
    }

    /**
     * @return Traversable<string,Index>
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
