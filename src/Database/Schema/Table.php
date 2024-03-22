<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use ArrayIterator;
use Countable;
use DateTimeImmutable;
use DateTimeInterface;
use IteratorAggregate;
use JsonSerializable;
use Pentagonal\Sso\Core\Database\Exceptions\RuntimeException;
use Stringable;
use Throwable;
use Traversable;
use function date;
use function get_object_vars;
use function intval;
use function is_int;
use function is_numeric;
use function is_string;
use function method_exists;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function strtoupper;
use function strval;
use function trim;
use function ucfirst;

/**
 * @link https://dev.mysql.com/doc/mysql-infoschema-excerpt/8.3/en/information-schema-tables-table.html
 */
class Table implements Stringable, IteratorAggregate, Countable, JsonSerializable
{
    final public const MYSQL_ENGINES = [
        'innodb' => 'InnoDB',
        'myisam' => 'MyISAM',
        'memory' => 'Memory',
        'csv' => 'CSV',
        'archive' => 'Archive',
        'blackhole' => 'Blackhole',
        'ndb' => 'NDB',
        'merge' => 'Merge',
        'federated' => 'Federated',
        'example' => 'Example'
    ];

    /**
     * @var string table name
     */
    private string $name;

    /**
     * @var string table type (base table, view, system view)
     */
    private string $type = 'BASE TABLE';

    /**
     * @var string table engine
     */
    private string $engine = 'InnoDB';

    /**
     * @var string mysql info version
     */
    private string $version = '';

    /**
     * @var string row-storage format (Fixed, Dynamic, Compressed, Redundant, Compact)
     */
    private string $rowFormat = 'Dynamic';

    /**
     * @var int the number of rows
     */
    private int $rows = 0;

    /**
     * @var int The average row length.
     */
    private int $averageRowLength = 0;

    /**
     * @var int
     *
     * For MyISAM, DATA_LENGTH is the length of the data file, in bytes.
     *
     * For InnoDB, DATA_LENGTH is the approximate amount of space allocated for the clustered index,
     * in bytes. Specifically, it is the clustered index size, in pages, multiplied by the InnoDB page size.
     * Refer to the notes at the end of this section for information regarding other storage engines.
     */
    private int $dataLength = 0;

    /**
     * @var ?int
     *
     * For MyISAM, MAX_DATA_LENGTH is maximum length of the data file.
     * This is the total number of bytes of data that can be stored in the table, given the data pointer size used.
     *
     * Unused for InnoDB.
     */
    private ?int $maxDataLength = null;

    /**
     * @var int
     *
     * For MyISAM, INDEX_LENGTH is the length of the index file, in bytes.
     * For InnoDB, INDEX_LENGTH is the approximate amount of space allocated for non-clustered indexes, in bytes.
     * Specifically, it is the sum of non-clustered index sizes, in pages, multiplied by the InnoDB page size.
     */
    private int $indexLength = 0;

    /**
     * @var int
     * The number of allocated but unused bytes.
     */
    private int $dataFree = 0;

    /**
     * @var ?int
     *
     * The next AUTO_INCREMENT value.
     */
    private ?int $autoIncrement = null;

    /**
     * @var ?DateTimeInterface the table was created
     */
    private ?DateTimeInterface $createTime = null;

    /**
     * @var ?DateTimeInterface
     * When the table was last updated. For some storage engines, this value is NULL.
     * Even with file-per-table mode with each InnoDB table in a separate .ibd file,
     * change buffering can delay write to the data file,
     * so the file modification time is different from the time of the last insert, update, or delete.
     * For MyISAM, the data file timestamp is used; however, on Windows the timestamp is not updated by updates,
     * so the value is inaccurate.
     * UPDATE_TIME displays a timestamp value for the last UPDATE, INSERT, or DELETE performed on InnoDB tables
     * that are not partitioned.
     * For MVCC, the timestamp value reflects the COMMIT time, which is considered the last update time.
     * Timestamps are not persisted
     * when the server is restarted or when the table is evicted from the InnoDB data dictionary cache.
     */
    private ?DateTimeInterface $updateTime = null;

    /**
     * @var ?DateTimeInterface
     * When the table was last checked.
     * Not all storage engines update this time, in which case, the value is always NULL.
     * For partitioned InnoDB tables, CHECK_TIME is always NULL.
     */
    private ?DateTimeInterface $checkTime = null;

    /**
     * @var string table collation
     */
    private string $collation = '';

    /**
     * @var ?string table checksum if any
     */
    private ?string $checksum = null;

    /**
     * @var ?string Extra options used with CREATE TABLE.
     */
    private ?string $createOptions = null;

    /**
     * @var ?string table comment
     */
    private ?string $comment = null;

    /**
     * @var bool
     */
    private bool $temporary = false;

    /**
     * @var ?Columns
     */
    private ?Columns $columns = null;

    /**
     * @var ?ForeignKeys
     */
    private ?ForeignKeys $foreignKeys = null;

    /**
     * @var ?Indexes
     */
    private ?Indexes $indexes = null;

    /**
     * Table constructor.
     *
     * @param string $name
     * @param array $definitions
     */
    public function __construct(
        string $name,
        array $definitions
    ) {
        $this->setName($name);
        foreach ($definitions as $defName => $definition) {
            if (!is_string($defName)) {
                continue;
            }
            $defName = match (strtolower($defName)) {
                'table_rows' => 'rows',
                'table_type' => 'type',
                'table_collation' => 'collation',
                'table_comment' => 'comment',
                'avg_row_length' => 'averageRowLength',
                default => str_replace('_', '', $defName)
            };
            $method = "set$defName";
            if (!method_exists($this, $method)) {
                continue;
            }
            $this->$method($definition);
        }
    }

    public function getForeignKeys(): ?ForeignKeys
    {
        return $this->foreignKeys;
    }

    /**
     * Set foreign keys
     *
     * @param ForeignKeys|null $foreignKeys
     * @return $this
     */
    public function setForeignKeys(?ForeignKeys $foreignKeys): static
    {
        $this->foreignKeys = $foreignKeys;
        return $this;
    }

    /**
     * Get indexes
     *
     * @return Indexes|null
     */
    public function getIndexes(): ?Indexes
    {
        return $this->indexes;
    }

    /**
     * Set indexes
     *
     * @param Indexes|null $indexes
     * @return $this
     */
    public function setIndexes(?Indexes $indexes): static
    {
        $this->indexes = $indexes;
        return $this;
    }

    /**
     * Get Table Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Table Name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = trim($name);
        return $this;
    }

    /**
     * Get Table Type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set Table Type
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): static
    {
        $currentType = match (str_replace([' ', '-'], '_', strtoupper(trim($type)))) {
            'BASE_TABLE' => 'BASE TABLE',
            'SYSTEM_VIEW' => 'SYSTEM VIEW',
            'VIEW' => 'VIEW',
            default => null
        };
        if (!$currentType) {
            throw new RuntimeException(
                sprintf(
                    'Table type "%s" is invalid. The valid one of : "%s"',
                    $type,
                    'BASE TABLE, SYSTEM VIEW, VIEW'
                )
            );
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Get Engine
     *
     * @return string
     */
    public function getEngine(): string
    {
        return $this->engine;
    }

    /**
     * Set Engine
     *
     * @param string $engine
     *
     * @return $this
     */
    public function setEngine(string $engine): static
    {
        $engine = trim($engine);
        $filteredEngine = self::MYSQL_ENGINES[strtolower($engine)]??$engine;
        $this->engine = $filteredEngine;
        return $this;
    }

    /**
     * Get Version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set Version
     *
     * @param string|int|float $version
     *
     * @return $this
     */
    public function setVersion(string|int|float $version): static
    {
        $version = strval($version);
        $version = trim($version);
        // commonly numeric
        $this->version = $version;
        return $this;
    }

    /**
     * Get Row Format
     *
     * @return string
     */
    public function getRowFormat(): string
    {
        return $this->rowFormat;
    }

    /**
     * Set Row Format
     *
     * @param string $rowFormat
     *
     * @return $this
     */
    public function setRowFormat(string $rowFormat): static
    {
        // Fixed, Dynamic, Compressed, Redundant, Compact
        $this->rowFormat = trim(ucfirst($rowFormat));
        return $this;
    }

    /**
     * Get Rows
     *
     * @return int
     */
    public function getRows(): int
    {
        return $this->rows;
    }

    public function setRows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    public function getAverageRowLength(): int
    {
        return $this->averageRowLength;
    }

    public function setAverageRowLength(int $averageRowLength): static
    {
        $this->averageRowLength = $averageRowLength;
        return $this;
    }

    public function getDataLength(): int
    {
        return $this->dataLength;
    }

    public function setDataLength(int $dataLength): static
    {
        $this->dataLength = $dataLength;
        return $this;
    }

    public function getMaxDataLength(): ?int
    {
        return $this->maxDataLength;
    }

    public function setMaxDataLength(?int $maxDataLength): static
    {
        $this->maxDataLength = $maxDataLength;
        return $this;
    }

    public function getIndexLength(): int
    {
        return $this->indexLength;
    }

    public function setIndexLength(int $indexLength): static
    {
        $this->indexLength = $indexLength;
        return $this;
    }

    public function getDataFree(): int
    {
        return $this->dataFree;
    }

    public function setDataFree(int $dataFree): static
    {
        $this->dataFree = $dataFree;
        return $this;
    }

    public function getAutoIncrement(): ?int
    {
        return $this->autoIncrement;
    }

    public function setAutoIncrement(?int $autoIncrement): static
    {
        $this->autoIncrement = $autoIncrement;
        return $this;
    }

    public function getCreateTime(): ?DateTimeInterface
    {
        return $this->createTime;
    }

    private function convertDate(DateTimeInterface|int|string|null $createTime) : ?DateTimeInterface
    {
        if ($createTime === null) {
            return null;
        }
        if (is_int($createTime)) {
            $createTime = date('c', $createTime);
        }
        if (is_numeric($createTime) && !str_contains($createTime, '.')) {
            $createTime = date('c', intval($createTime));
        }
        try {
            return new DateTimeImmutable($createTime);
        } catch (Throwable) {
        }
        return null;
    }

    public function setCreateTime(DateTimeInterface|int|string|null $createTime): static
    {
        $this->createTime = $this->convertDate($createTime);
        return $this;
    }

    public function getUpdateTime(): ?DateTimeInterface
    {
        return $this->updateTime;
    }

    public function setUpdateTime(DateTimeInterface|int|string|null $updateTime): static
    {
        $this->updateTime = $this->convertDate($updateTime);
        return $this;
    }

    public function getCheckTime(): ?DateTimeInterface
    {
        return $this->checkTime;
    }

    public function setCheckTime(DateTimeInterface|int|string|null $checkTime): static
    {
        $this->checkTime = $this->convertDate($checkTime);
        return $this;
    }

    public function getCollation(): string
    {
        return $this->collation;
    }

    public function setCollation(string $collation): static
    {
        $originalCollation = $collation;
        $collation = strtolower(trim($collation));
        $collation = str_replace('-', '_', $collation);
        if (!preg_match(
            '~^([a-z0-9]+)_([a-z0-9]+)(_[a-z0-9]+(_[a-z0-9_]*[a-z0-9])?)?$~',
            $collation
        )) {
            throw new RuntimeException(
                sprintf('Collation "%s" is invalid', $originalCollation)
            );
        }

        $this->collation = $collation;
        return $this;
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(?string $checksum): static
    {
        $this->checksum = $checksum;
        return $this;
    }

    public function getCreateOptions(): ?string
    {
        return $this->createOptions;
    }

    public function setCreateOptions(?string $createOptions): static
    {
        $createOptions = $createOptions ? trim($createOptions) : null;
        $this->createOptions = $createOptions;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    public function setTemporary(bool|string $temporary): static
    {
        if (is_string($temporary)) {
            $temporary = strtolower(trim($temporary));
            $temporary = $temporary !== 'N';
        }
        $this->temporary = $temporary;
        return $this;
    }

    public function getColumns(): ?Columns
    {
        return $this->columns;
    }

    public function setColumns(?Columns $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function __toString() : string
    {
        return $this->getName();
    }

    /**
     * @return Traversable<string, Column>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getColumns());
    }

    /**
     * @return int count of columns
     */
    public function count(): int
    {
        return count($this->getColumns());
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
