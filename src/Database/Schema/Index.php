<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Stringable;
use function strtolower;

class Index implements Stringable
{
    public const FULLTEXT = 'FULLTEXT';
    public const SPATIAL = 'SPATIAL';

    public const BTREE = 'BTREE';

    public const HASH = 'HASH';

    /**
     * @var array{
     *     name: string,
     *     unique: bool,
     *     type: string,
     *     comment: ?string,
     *     block_size: ?int,
     *     columns: array<string, array{
     *          name: string,
     *          position: int,
     *          length: ?int,
     *          cardinality: int,
     *          collation: ?string
     *      }>
     * }
     */
    protected array $attributes = [
        'name' => '',
        'unique' => false,
        'type' => self::BTREE,
        'comment' => null,
        'block_size' => null,
        'columns' => [],
    ];

    public function __construct(
        string $name,
        bool $unique = false,
        string $type = self::BTREE,
        ?int $blockSize = null,
        ?string $comment = null
    ) {
        $this->attributes['name'] = $name;
        $this->attributes['unique'] = $unique;
        $this->attributes['type'] = $type;
        $this->attributes['block_size'] = $blockSize;
        $this->attributes['comment'] = $comment;
    }

    /**
     * Add Column
     *
     * @param string $column
     * @param int|null $position
     * @param int|null $length
     * @param int $cardinality
     * @param string|null $collation
     * @return $this
     */
    public function addColumn(
        string $column,
        ?int $position = null,
        ?int $length = null,
        int $cardinality = 0,
        ?string $collation = null,
    ): static {
        $position = $position === null ? count($this->attributes['column']) : $position;
        $this->attributes['columns'][strtolower($column)] = [
            'name' => $column,
            'position' => $position,
            'length' => $length,
            'cardinality' => $cardinality,
            'collation' => $collation,
        ];
        return $this;
    }

    /**
     * Get Column
     *
     * @return array<string, array{
     *     name: string,
     *     position: int,
     *     length: ?int
     * }>
     */
    public function getColumns(): array
    {
        return $this->attributes['columns'];
    }

    /**
     * Remove Column
     *
     * @param string $column
     * @return $this
     */
    public function removeColumn(string $column): static
    {
        unset($this->attributes['columns'][strtolower($column)]);
        return $this;
    }

    /**
     * @return string name of the index
     */
    public function getName(): string
    {
        return $this->attributes['name'];
    }

    /**
     * @return string type of the index
     */
    public function getType(): string
    {
        return $this->attributes['type'];
    }

    /**
     * Set type
     *
     * @param string $type type of the index
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->attributes['type'] = $type;
        return $this;
    }

    public function setComment(?string $comment): static
    {
        $this->attributes['comment'] = $comment;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->attributes['comment'];
    }

    public function setBlockSize(?int $blockSize): static
    {
        $this->attributes['block_size'] = $blockSize;
        return $this;
    }

    public function getBlockSize(): ?int
    {
        return $this->attributes['block_size'];
    }

    public function setUnique(bool $unique): static
    {
        $this->attributes['unique'] = $unique;
        return $this;
    }

    public function isUnique(): bool
    {
        return $this->attributes['unique'];
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return string string representation of the object
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
