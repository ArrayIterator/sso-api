<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Indexes implements IteratorAggregate, Countable
{
    /**
     * @var Table $table The table to which the indexes belong.
     */
    protected Table $table;

    /**
     * @var array<string, Index>
     */
    protected array $indexes = [];

    /**
     * @param Table $table The table to which the indexes belong.
     * @param Index ...$indexes The indexes to add.
     */
    public function __construct(
        Table $table,
        Index ...$indexes
    ) {
        $this->table = $table;
        foreach ($indexes as $index) {
            $this->add($index);
        }
    }

    /**
     * Adds an index to the table.
     *
     * @param Index $index The index to add.
     */
    public function add(Index $index): void
    {
        $this->indexes[$index->getName()] = $index;
    }

    /**
     * Gets all indexes.
     *
     * @return array<string, Index>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Gets an index by name.
     *
     * @param string $name The name of the index to get.
     *
     * @return ?Index The index, or null if it does not exist.
     */
    public function get(string $name): ?Index
    {
        return $this->indexes[$name] ?? null;
    }

    /**
     * Checks if an index exists.
     *
     * @param string $name The name of the index to check.
     *
     * @return bool True if the index exists, false if not.
     */
    public function has(string $name): bool
    {
        return isset($this->indexes[$name]);
    }

    /**
     * Removes an index.
     *
     * @param string $name The name of the index to remove.
     */
    public function remove(string $name): ?Index
    {
        $index = $this->indexes[$name] ?? null;
        unset($this->indexes[$name]);
        return $index;
    }

    /**
     * Gets the table to which the indexes belong.
     *
     * @return Table The table to which the indexes belong.
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * @return Traversable<string, Index>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getIndexes());
    }

    /**
     * @return int The number of indexes.
     */
    public function count(): int
    {
        return count($this->getIndexes());
    }
}
