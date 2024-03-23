<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Pentagonal\Sso\Core\Database\Schema\Attributes\Collations;
use function is_string;

class Table
{
    /**
     * @var array{
     *     name: string,
     *     engine: string,
     *     collation: ?string,
     *     auto_increment: ?int,
     *     comment: ?string,
     *     row_format: string,
     *     create_options: string,
     *     temporary: bool
     * }
     */
    protected array $attributes = [
        'name' => '',
        'engine' => null,
        'collation' => null,
        'auto_increment' => null,
        'comment' => null,
        'row_format' => null,
        'create_options' => null,
        'temporary' => false,
    ];

    /**
     * @var Columns Columns
     */
    protected Columns $columns;

    /**
     * @var Indexes Indexes
     */
    protected Indexes $indexes;

    /**
     * @var ForeignKeys ForeignKeys
     */
    protected ForeignKeys $foreignKeys;

    /**
     * Table constructor.
     *
     * @param string $tableName
     * @param array $attributes
     */
    public function __construct(
        string $tableName,
        array $attributes = []
    ) {
        $tableName = trim($tableName);
        $this->attributes['name'] = $tableName;
        unset($attributes['name']);
        foreach ($attributes as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $method = 'set' . str_replace('_', '', $key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * Set Engine
     *
     * @param ?string $engine
     * @return $this
     */
    public function setEngine(?string $engine) : static
    {
        $this->attributes['engine'] = $engine;
        return $this;
    }

    /**
     * Set Collation
     *
     * @param ?string $collation
     * @return $this
     */
    public function setCollation(?string $collation) : static
    {
        $this->attributes['collation'] = $collation
            ? Collations::normalizeCollation($collation)
            : null;
        return $this;
    }

    /**
     * Set Auto Increment
     *
     * @param int|null $autoIncrement
     * @return $this
     */
    public function setAutoIncrement(?int $autoIncrement) : static
    {
        $this->attributes['auto_increment'] = $autoIncrement;
        return $this;
    }

    /**
     * Set Comment
     *
     * @param string|null $comment
     * @return $this
     */
    public function setComment(?string $comment) : static
    {
        $this->attributes['comment'] = $comment;
        return $this;
    }

    /**
     * Set Row Format
     *
     * @param ?string $rowFormat
     * @return $this
     */
    public function setRowFormat(?string $rowFormat) : static
    {
        $this->attributes['row_format'] = $rowFormat;
        return $this;
    }

    /**
     * Set Create Options
     *
     * @param ?string $createOptions
     * @return $this
     */
    public function setCreateOptions(?string $createOptions) : static
    {
        $this->attributes['create_options'] = $createOptions;
        return $this;
    }

    /**
     * Set Temporary
     *
     * @param bool $temporary
     * @return $this
     */
    public function setTemporary(bool $temporary) : static
    {
        $this->attributes['temporary'] = $temporary;
        return $this;
    }

    /**
     * Set Columns
     *
     * @param Columns $columns
     * @return $this
     */
    public function setColumns(Columns $columns) : static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return Columns Columns
     */
    public function getColumns() : Columns
    {
        return $this->columns ??= new Columns();
    }

    /**
     * @return Indexes Indexes
     */
    public function getIndexes() : Indexes
    {
        return $this->indexes ??= new Indexes($this);
    }

    /**
     * @return ForeignKeys ForeignKeys
     */
    public function getForeignKeys() : ForeignKeys
    {
        return $this->foreignKeys ??= new ForeignKeys($this);
    }

    /**
     * @return string Table Name
     */
    public function getName() : string
    {
        return $this->attributes['name'];
    }

    /**
     * @return string Engine
     */
    public function getEngine() : string
    {
        return $this->attributes['engine'];
    }

    /**
     * @return ?string Collation
     */
    public function getCollation() : ?string
    {
        return $this->attributes['collation'];
    }

    /**
     * @return ?int Auto Increment
     */
    public function getAutoIncrement() : ?int
    {
        return $this->attributes['auto_increment'];
    }

    /**
     * @return ?string Comment
     */
    public function getComment() : ?string
    {
        return $this->attributes['comment'];
    }

    /**
     * @return ?string Row Format
     */
    public function getRowFormat() : ?string
    {
        return $this->attributes['row_format'];
    }

    /**
     * @return ?string Create Options
     */
    public function getCreateOptions() : ?string
    {
        return $this->attributes['create_options'];
    }

    /**
     * @return bool Temporary
     */
    public function isTemporary() : bool
    {
        return $this->attributes['temporary'];
    }

    public function __clone(): void
    {
        $this->columns = clone $this->getColumns();
        $this->indexes = clone $this->getIndexes();
        $this->foreignKeys = clone $this->getForeignKeys();
    }
}
