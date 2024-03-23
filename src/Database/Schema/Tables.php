<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Tables implements Countable, IteratorAggregate
{
    /**
     * @var Schema The schema.
     */
    protected Schema $schema;

    /**
     * @var array<string, Table>
     */
    protected array $tables = [];

    /**
     * Tables constructor.
     *
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * Check if has Table
     *
     * @param string|Table $tableName
     * @return bool
     */
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
     * Get Table
     *
     * @param string $table
     * @return ?Table
     */
    public function get(string $table) : ?Table
    {
        return $this->tables[strtolower($table)] ?? null;
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
        $table = $this->tables[$tableName] ?? null;
        unset($this->tables[$tableName]);
        return $table;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @return Traversable<string,Table>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getTables());
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->getTables());
    }

    /**
     * Clone the tables.
     */
    public function __clone(): void
    {
        foreach ($this->tables as $key => $table) {
            $this->tables[$key] = clone $table;
        }
    }
}
