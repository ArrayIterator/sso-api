<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Stringable;
use function strtolower;

class ForeignKey implements Stringable
{
    public const ACTION_NO_ACTION = 'NO ACTION';

    public const ACTION_RESTRICT = 'RESTRICT';

    public const ACTION_CASCADE = 'CASCADE';

    public const ACTION_SET_NULL = 'SET NULL';

    public const ACTION_SET_DEFAULT = 'SET DEFAULT';

    /**
     * @var string Database Name
     */
    protected string $referenceDatabase;

    /**
     * @var string Table Name
     */
    protected string $referenceTable;

    /**
     * @var array{
     *     name: string,
     *     onUpdate: string,
     *     onDelete: string,
     *     columns: array<string, array{
     *          column: string,
     *          reference: string,
     *          referenceColumn: string
     *      }>
     * }
     */
    protected array $attributes = [
        'name' => '',
        'onUpdate' => self::ACTION_NO_ACTION,
        'onDelete' => self::ACTION_NO_ACTION,
        'columns' => [],
    ];

    /**
     * ForeignKey constructor.
     *
     * @param string $name
     * @param string $referenceDatabase
     * @param string $referenceTable
     * @param string $onUpdate action on update (default: NO ACTION)
     * @param string $onDelete action on delete (default: NO ACTION)
     */
    public function __construct(
        string $name,
        string $referenceDatabase,
        string $referenceTable,
        string $onUpdate = self::ACTION_NO_ACTION,
        string $onDelete = self::ACTION_NO_ACTION
    ) {
        $this->referenceTable = $referenceTable;
        $this->referenceDatabase = $referenceDatabase;
        $this->attributes['name'] = $name;
        $this->attributes['onUpdate'] = $onUpdate;
        $this->attributes['onDelete'] = $onDelete;
    }

    public function getReferenceDatabase(): string
    {
        return $this->referenceDatabase;
    }

    public function getReferenceTable(): string
    {
        return $this->referenceTable;
    }

    /**
     * Add Column
     *
     * @param string $column
     * @param string $reference
     * @param string $referenceColumn
     * @param int $ordinalPosition
     * @return $this
     */
    public function addColumn(
        string $column,
        string $reference,
        string $referenceColumn,
        int $ordinalPosition
    ): static {
        $this->attributes['columns'][strtolower($column)] = [
            'column' => $column,
            'reference' => $reference,
            'referenceColumn' => $referenceColumn,
            'ordinalPosition' => $ordinalPosition
        ];
        return $this;
    }

    public function getAttribute(string $attribute) : mixed
    {
        return $this->attributes[$attribute] ?? null;
    }

    /**
     * Get Attributes
     *
     * @return array{
     *      name: string,
     *      onUpdate: string,
     *      onDelete: string,
     *      columns: array<string, array{
     *           column: string,
     *           reference: string,
     *           referenceColumn: string
     *       }>
     *  }
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->attributes['name'];
    }

    /**
     * Get On Update
     *
     * @return string
     */
    public function getOnUpdate() : string
    {
        return $this->attributes['onUpdate'];
    }

    /**
     * Get On Delete
     *
     * @return string
     */
    public function getOnDelete() : string
    {
        return $this->attributes['onDelete'];
    }

    /**
     * Get Columns
     *
     * @return array<string, array{
     *      column: string,
     *      reference: string,
     *      referenceColumn: string
     *  }>
     */
    public function getColumns() : array
    {
        return $this->attributes['columns'];
    }

    /**
     * Get Column
     *
     * @param string $column
     * @return ?array{
     *      column: string,
     *      reference: string,
     *      referenceColumn: string
     *  }
     */
    public function getColumn(string $column) : ?array
    {
        return $this->attributes['columns'][strtolower($column)] ?? null;
    }

    /**
     * Check if it has Column
     *
     * @param string $column
     * @return bool
     */
    public function hasColumn(string $column) : bool
    {
        return isset($this->attributes['columns'][strtolower($column)]);
    }

    /**
     * Remove Column
     *
     * @param string $column
     * @return $this
     */
    public function removeColumn(string $column) : static
    {
        unset($this->attributes['columns'][strtolower($column)]);
        return $this;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->attributes['name'] = $name;
        return $this;
    }

    /**
     * Set onUpdate
     *
     * @param string $onUpdate
     * @return $this
     */
    public function setOnUpdate(string $onUpdate) : static
    {
        $this->attributes['onUpdate'] = $onUpdate;
        return $this;
    }

    /**
     * Set onDelete
     *
     * @param string $onDelete
     * @return $this
     */
    public function setOnDelete(string $onDelete) : static
    {
        $this->attributes['onDelete'] = $onDelete;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
