<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Pentagonal\Sso\Core\Database\Connection;
use Traversable;

class ForeignKeys implements IteratorAggregate, Countable
{
    /**
     * @var Table The table.
     */
    protected Table $table;

    /**
     * @var array<string, ForeignKey> The foreign keys.
     */
    protected array $foreignKeys = [];

    /**
     * @param Table $table The table.
     * @param ForeignKey ...$foreignKeys The foreign keys.
     */
    public function __construct(Table $table, ForeignKey ...$foreignKeys)
    {
        $this->table = $table;
        foreach ($foreignKeys as $foreignKey) {
            $this->add($foreignKey);
        }
    }

    /**
     * Adds a foreign key to the table.
     *
     * @param ForeignKey $foreignKey The foreign key to add.
     */
    public function add(ForeignKey $foreignKey): void
    {
        $this->foreignKeys[$foreignKey->getName()] = $foreignKey;
    }

    /**
     * Gets all foreign keys.
     *
     * @return array<string, ForeignKey>
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    /**
     * Gets a foreign key by name.
     *
     * @param string $name The name of the foreign key to get.
     * @return ?ForeignKey
     */
    public function get(string $name): ?ForeignKey
    {
        return $this->foreignKeys[$name] ?? null;
    }

    /**
     * Checks if the table has a foreign key.
     *
     * @param string $name The name of the foreign key to check.
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->foreignKeys[$name]);
    }

    /**
     * Removes a foreign key from the table.
     *
     * @param string $name The name of the foreign key to remove.
     * @return ?ForeignKey The removed foreign key, or null if it does not exist.
     */
    public function remove(string $name): ?ForeignKey
    {
        $foreignKey = $this->foreignKeys[$name]??null;
        unset($this->foreignKeys[$name]);
        return $foreignKey;
    }

    /**
     * @return Table The table.
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    public function getAlterSql(Connection $connection): string
    {
        $sql = '';
        foreach ($this->getForeignKeys() as $foreignKey) {
            // $sql .= $foreignKey->getCreateSql($connection) . ";\n";
        }
        return $sql;
    }

    /**
     * @return Traversable<string, ForeignKey> The foreign keys.
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getForeignKeys());
    }

    /**
     * @return int The number of foreign keys.
     */
    public function count(): int
    {
        return count($this->getForeignKeys());
    }
}
