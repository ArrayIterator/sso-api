<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use function count;

class ForeignKey implements IteratorAggregate, Countable, JsonSerializable
{
    private string $name;

    /**
     * @var array<array{
     *     ordinalPosition: int,
     *     database: string,
     *     table: Table,
     *     column: Column,
     *     foreignDatabase: string,
     *     foreignTable: Table,
     *     foreignColumn: Column,
     *     onUpdate: string,
     *     onDelete: string,
     *     index: Index
     * }>
     */
    private array $foreignKeys = [];

    public function __construct(
        string $name
    ) {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<array{
     *      ordinalPosition: int,
     *      database: string,
     *      table: Table,
     *      column: Column,
     *      foreignDatabase: string,
     *      foreignTable: Table,
     *      foreignColumn: Column,
     *      onUpdate: string,
     *      onDelete: string
     *  }>
     */
    public function all() : array
    {
        return $this->foreignKeys;
    }

    /**
     * Add ForeignKey
     *
     * @param string $database
     * @param Table $table
     * @param Column $column
     * @param string $foreignDatabase
     * @param Table $foreignTable
     * @param Column $foreignColumn
     * @param string $onUpdate
     * @param string $onDelete
     * @param int $ordinalPosition
     * @param Index $index
     * @return $this
     */
    public function add(
        string $database,
        Table $table,
        Column $column,
        string $foreignDatabase,
        Table $foreignTable,
        Column $foreignColumn,
        string $onUpdate,
        string $onDelete,
        int $ordinalPosition,
        Index $index
    ) : static {
        $this->foreignKeys[] = [
            'ordinalPosition' => $ordinalPosition,
            'database' => $database,
            'table' => $table,
            'column' => $column,
            'foreignDatabase' => $foreignDatabase,
            'foreignTable' => $foreignTable,
            'foreignColumn' => $foreignColumn,
            'onUpdate' => $onUpdate,
            'onDelete' => $onDelete,
            'index' => $index
        ];
        usort(
            $this->foreignKeys,
            fn ($a, $b) =>  $a['ordinalPosition'] <=> $b['ordinalPosition']
        );
        return $this;
    }

    /**
     * @return Traversable<array{
     *     ordinalPosition: int,
     *     database: string,
     *     table: Table,
     *     column: Column,
     *     foreignDatabase: string,
     *     foreignTable: Table,
     *     foreignColumn: Column,
     *     onUpdate: string,
     *     onDelete: string,
     *     index: Index
     * }>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    /**
     * @return int count of foreign keys
     */
    public function count(): int
    {
        return count($this->all());
    }

    public function jsonSerialize(): array
    {
        $data = $this->all();
        foreach ($data as &$item) {
            $item['table'] = $item['table']->getName();
            $item['column'] = $item['column']->getName();
            $item['foreignTable'] = $item['foreignTable']->getName();
            $item['foreignColumn'] = $item['foreignColumn']->getName();
            $item['index'] = $item['index']->getName();
        }
        return $data;
    }
}
