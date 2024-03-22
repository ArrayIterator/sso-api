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

class Tables implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var array<string, Table>
     */
    protected array $tables = [];

    public function __construct(Table ...$tables)
    {
        foreach ($tables as $table) {
            $this->tables[strtolower($table->getName())] = $table;
        }
    }

    public function has(string|Table $tableName) : bool
    {
        return isset($this->tables[strtolower((string) $tableName)]);
    }

    /**
     * Add Table
     *
     * @param Table $table
     * @return $this
     */
    public function add(Table $table) : static
    {
        $this->tables[strtolower($table->getName())] = $table;
        return $this;
    }

    /**
     * Remove Table
     *
     * @param string|Table $tableName
     * @return ?Table
     */
    public function remove(string|Table $tableName) : ?Table
    {
        $tableName = strtolower((string) $tableName);
        if (isset($this->tables[$tableName])) {
            $table = $this->tables[$tableName];
            unset($this->tables[$tableName]);
            return $table;
        }
        return null;
    }

    /**
     * @param string $tableName
     * @return Table|null
     */
    public function get(string $tableName) : ?Table
    {
        return $this->tables[strtolower($tableName)] ?? null;
    }

    /**
     * @return array<string, Table>
     */
    public function all() : array
    {
        return $this->tables;
    }

    /**
     * @return Traversable<string,Table>
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
