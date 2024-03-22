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

class Columns implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var array<string, Column>
     */
    protected array $columns = [];

    public function __construct(Column ...$columns)
    {
        foreach ($columns as $column) {
            $this->columns[strtolower($column->getName())] = $column;
        }
    }

    /**
     * Check if has Column
     *
     * @param string|Column $tableName
     * @return bool
     */
    public function has(string|Column $tableName) : bool
    {
        return isset($this->columns[strtolower((string) $tableName)]);
    }

    /**
     * Add Table
     *
     * @param Column $column
     * @return $this
     */
    public function add(Column $column) : static
    {
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
     * Remove Table
     *
     * @param string|Column $columName
     * @return Table|null
     */
    public function remove(string|Column $columName) : ?Column
    {
        $columName = strtolower((string) $columName);
        if (isset($this->columns[$columName])) {
            $table = $this->columns[$columName];
            unset($this->columns[$columName]);
            return $table;
        }
        return null;
    }

    /**
     * @return array<string, Column>
     */
    public function all() : array
    {
        return $this->columns;
    }

    /**
     * @return Traversable<string,Column>
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
