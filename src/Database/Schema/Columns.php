<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Columns implements IteratorAggregate, Countable
{
    /**
     * @var array<string, Column> The columns.
     */
    protected array $columns = [];

    /**
     * @var bool Whether the columns have been ordered.
     */
    private bool $ordered = false;

    /**
     * @param Column ...$columns
     */
    public function __construct(Column ...$columns)
    {
        foreach ($columns as $column) {
            $this->add($column);
        }
    }

    /**
     * Add Column
     *
     * @param Column $column
     * @return $this
     */
    public function add(Column $column) : static
    {
        $this->ordered = true;
        $this->columns[strtolower($column->getName())] = $column;
        return $this;
    }

    /**
     * Get Column
     *
     * @param string $column
     * @return ?Column
     */
    public function get(string $column) : ?Column
    {
        return $this->columns[strtolower($column)] ?? null;
    }

    /**
     * Check if has Column
     *
     * @param string|Column $columnName
     * @return bool
     */
    public function has(string|Column $columnName) : bool
    {
        return isset($this->columns[strtolower((string) $columnName)]);
    }

    /**
     * Remove Column
     *
     * @param string $columnName
     * @return ?Column
     */
    public function remove(string $columnName) : ?Column
    {
        $columnName = strtolower($columnName);
        $column = $this->columns[$columnName] ?? null;
        unset($this->columns[$columnName]);
        return $column;
    }

    /**
     * Get Columns
     *
     * @return array
     */
    public function getColumns() : array
    {
        if (! $this->ordered) {
            $this->ordered = true;
            // sort by ordinal
            uasort($this->columns, function (Column $a, Column $b) {
                // sort by ordinal
                return $a->getOrdinalPosition() <=> $b->getOrdinalPosition();
            });
        }
        return $this->columns;
    }

    /**
     * @return Traversable<string, Column> The columns.
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->getColumns());
    }

    /**
     * @return int The number of columns.
     */
    public function count(): int
    {
        return count($this->columns);
    }

    public function __clone(): void
    {
        foreach ($this->columns as $key => $column) {
            $this->columns[$key] = clone $column;
        }
    }
}
