<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use function count;
use function is_string;
use function strtolower;
use function strtoupper;
use function uasort;

class Index implements IteratorAggregate, Countable, JsonSerializable
{
    /**
     * @var string index name
     */
    private string $name;

    /**
     * @var array<string, array{
     *     column: Column,
     *     isUnique: bool,
     *     sequence: int,
     *     collation: ?string,
     *     cardinality: int,
     *     subPart: ?string,
     *     packed: mixed,
     *     nullable: bool,
     *     indexType: string,
     *     comment: ?string,
     *     ignored: bool
     * }>
     */
    private array $definitions = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get columns
     *
     * @return array<Column>
     */
    public function all() : array
    {
        return $this->definitions;
    }

    /**
     * @return string index name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Column $column
     * @param bool $isUnique
     * @param int $sequence
     * @param string $collation
     * @param int $cardinality
     * @param string|null $subPart
     * @param string $packed
     * @param bool|string $nullable
     * @param string $indexType
     * @param string|null $comment
     * @param bool|string $ignored
     * @return $this
     */
    public function add(
        Column $column,
        bool $isUnique,
        int $sequence,
        string $collation,
        int $cardinality,
        ?string $subPart,
        mixed $packed,
        bool|string $nullable,
        string $indexType,
        ?string $comment,
        bool|string $ignored
    ) : static {
        $ignored = is_string($ignored)
            ? strtolower($ignored) === 'yes'
            : $ignored;
        $nullable = is_string($nullable)
            ? strtolower($nullable) === 'yes'
            : $nullable;
        $subPart = $subPart?:null;
        $this->definitions[strtolower($column->getName())] = [
            'column' => $column,
            'isUnique' => $isUnique,
            'sequence' => $sequence,
            'collation' => $collation,
            'cardinality' => $cardinality,
            'subPart' => $subPart,
            'packed' => $packed,
            'nullable' => $nullable,
            'indexType' => strtoupper($indexType),
            'comment' => $comment,
            'ignored' => $ignored
        ];
        uasort($this->definitions, fn($a, $b) => $a['sequence'] <=> $b['sequence']);
        return $this;
    }

    /**
     * @return Traversable<string, array{
     *      column: Column,
     *      isUnique: bool,
     *      sequence: int,
     *      collation: ?string,
     *      cardinality: int,
     *      subPart: ?string,
     *      packed: mixed,
     *      nullable: bool,
     *      indexType: string,
     *      comment: ?string,
     *      ignored: bool
     *  }>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    /**
     * @return int count of columns
     */
    public function count(): int
    {
        return count($this->definitions);
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
