<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use JsonSerializable;
use Pentagonal\Sso\Core\Database\Exceptions\RuntimeException;
use function array_filter;
use function array_map;
use function get_object_vars;
use function is_numeric;
use function is_string;
use function method_exists;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function strtoupper;
use function trim;

class Column implements JsonSerializable
{
    public const COLUMNS_TYPE = [
        'varchar' => 'varchar',
        'string' => 'varchar',
        'text' => 'text',
        'mediumtext' => 'mediumtext',
        'longtext' => 'longtext',
        'char' => 'char',
        'date' => 'date',
        'datetime' => 'datetime',
        'timestamp' => 'timestamp',
        'time' => 'time',
        'year' => 'year',
        'enum' => 'enum',
        'set' => 'set',
        'binary' => 'binary',
        'varbinary' => 'varbinary',
        'blob' => 'blob',
        'tinyblob' => 'tinyblob',
        'mediumblob' => 'mediumblob',
        'longblob' => 'longblob',
        'bit' => 'bit',
        'int' => 'int',
        'integer' => 'int',
        'tinyint' => 'tinyint',
        'smallint' => 'smallint',
        'mediumint' => 'mediumint',
        'bigint' => 'bigint',
        'float' => 'float',
        'double' => 'double',
        'decimal' => 'decimal',
        'real' => 'real',
        'numeric' => 'numeric',
        'bool' => 'bool',
        'boolean' => 'bool',
        'json' => 'json',
        'uuid' => 'uuid',
        'inet-6' => 'inet6',
        'inet6' => 'inet6',
        'geometry' => 'geometry',
        'point' => 'point',
        'linestring' => 'linestring',
        'polygon' => 'polygon',
        'multipoint' => 'multipoint',
        'multilinestring' => 'multilinestring',
        'multipolygon' => 'multipolygon',
        'geometrycollection' => 'geometrycollection',
    ];

    public const UNSUPPORTED_LENGTH = [
        'text',
        'mediumtext',
        'longtext',
        'blob',
        'tinyblob',
        'mediumblob',
        'longblob',
        'enum',
        'set',
        'json',
        'uuid',
        'geometry',
        'point',
        'linestring',
        'polygon',
        'multipoint',
        'multilinestring',
        'multipolygon',
        'geometrycollection',
        'date',
        'datetime',
        'timestamp',
        'time',
        'year',
        'bool',
        'boolean',
    ];

    private string $name;
    private int $ordinalPosition = 0;
    private ?string $defaultValue = null;
    private bool $nullable = false;
    private string $dataType = '';
    private ?int $maximumLength = null;
    private ?int $octetLength = null;
    private ?int $numericPrecision = null;
    private ?int $scale = null;
    private ?int $datePrecision = null;
    private ?string $charsetName = null;
    private ?string $collation = null;
    private string $columnType = '';
    private string $columnKey = '';
    private ?string $extra = null;

    private array $privileges = [];

    private ?string $comment = null;

    private bool $generated = false;

    private ?string $generationExpression = null;

    private ?int $length = null;

    public function __construct(string $name, array $definitions)
    {
        $this->name = trim($name);
        //print_r($definitions);
        foreach ($definitions as $defName => $definition) {
            if (!is_string($defName)) {
                continue;
            }
            $lowerDefName = strtolower($defName);
            $defName = match ($lowerDefName) {
                'column_default' => 'DefaultValue',
                'column_comment' => 'Comment',
                'is_nullable' => 'Nullable',
                'is_generated' => 'Generated',
                'character_set_name' => 'CharsetName',
                'collation_name' => 'Collation',
                'character_maximum_length' => 'MaximumLength',
                'character_octet_length' => 'OctetLength',
                default => str_replace('_', '', $defName)
            };
            $method = "set$defName";
            if (!method_exists($this, $method)) {
                continue;
            }
            $this->$method($definition);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = trim($name);
        return $this;
    }

    public function getOrdinalPosition(): int
    {
        return $this->ordinalPosition;
    }

    public function setOrdinalPosition(int $ordinalPosition): static
    {
        $this->ordinalPosition = $ordinalPosition;
        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool|string $nullable): static
    {
        if (is_string($nullable)) {
            $nullable = strtoupper($nullable) === 'YES';
        }
        $this->nullable = $nullable;
        return $this;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function setDataType(string $dataType): static
    {
        $dataType = strtolower($dataType);
        if (isset(self::COLUMNS_TYPE[$dataType])) {
            $dataType = self::COLUMNS_TYPE[$dataType];
        }
        $this->dataType = $dataType;
        return $this;
    }

    public function getMaximumLength(): ?int
    {
        return $this->maximumLength;
    }

    public function setMaximumLength(?int $maximumLength): static
    {
        $this->maximumLength = $maximumLength;
        return $this;
    }

    public function getOctetLength(): ?int
    {
        return $this->octetLength;
    }

    public function setOctetLength(?int $octetLength): static
    {
        $this->octetLength = $octetLength;
        return $this;
    }

    public function getNumericPrecision(): ?int
    {
        return $this->numericPrecision;
    }

    public function setNumericPrecision(?int $numericPrecision): static
    {
        $this->numericPrecision = $numericPrecision;
        if ($this->numericPrecision !== null && $this->numericPrecision < 0) {
            $this->numericPrecision = null;
        }
        return $this;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function setScale(?int $scale): static
    {
        $this->scale = $scale;
        return $this;
    }

    public function getDatePrecision(): ?int
    {
        return $this->datePrecision;
    }

    public function setDatePrecision(?int $datePrecision): static
    {
        $this->datePrecision = $datePrecision;
        if ($this->datePrecision !== null && $this->datePrecision < 0) {
            $this->datePrecision = null;
        }
        return $this;
    }

    public function getCharsetName(): ?string
    {
        return $this->charsetName;
    }

    public function setCharsetName(?string $charsetName): static
    {
        $this->charsetName = is_string($charsetName)
            ? strtolower(trim($charsetName))
            : null;
        return $this;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function setCollation(?string $collation): static
    {
        if ($collation === null) {
            $this->collation = null;
            return $this;
        }

        $originalCollation = $collation;
        $collation = strtolower(trim($collation));
        $collation = str_replace('-', '_', $collation);
        if (!preg_match(
            '~^([a-z0-9]+)_([a-z0-9]+)(_[a-z0-9]+(_[a-z0-9_]*[a-z0-9])?)?$~',
            $collation,
            $match
        )) {
            throw new RuntimeException(
                sprintf('Collation "%s" is invalid', $originalCollation)
            );
        }
        if (!$this->charsetName) {
            $this->charsetName = $match[1];
        }
        $this->collation = $collation;
        return $this;
    }

    public function getColumnType(): string
    {
        return $this->columnType;
    }

    public function setColumnType(string $columnType): static
    {
        $this->columnType = trim($columnType);
        preg_match('~^([^(\s]+)(?:\s*\(([^)]+)\))?$~', $this->columnType, $match);
        if ($match) {
            $this->setDataType($match[1]);
            if (isset($match[2])
                && is_numeric(($length = trim($match[2])))
            ) {
                $this->setLength((int) $length);
            }
        }

        return $this;
    }

    public function getColumnKey(): string
    {
        return $this->columnKey;
    }

    public function setColumnKey(string $columnKey): static
    {
        $this->columnKey = $columnKey;
        return $this;
    }

    public function getExtra(): ?string
    {
        return $this->extra;
    }

    public function setExtra(?string $extra): static
    {
        $this->extra = ($extra ? trim($extra) : null)?:null;
        return $this;
    }

    public function isPrimaryKey() : bool
    {
        return str_contains($this->columnKey, 'PRI');
    }

    /**
     * @return bool
     */
    public function isAutoIncrement() : bool
    {
        return str_contains(strtolower($this->extra??''), 'auto_increment');
    }

    public function getPrivileges(): array
    {
        return $this->privileges;
    }

    public function setPrivileges(array|string $privileges): static
    {
        if (is_string($privileges)) {
            $privileges = explode(',', $privileges);
        }
        $privileges = array_filter($privileges, 'is_string');
        $privileges = array_map('trim', $privileges);
        $this->privileges = array_values(array_map('strtoupper', $privileges));
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

    public function isGenerated(): bool
    {
        return $this->generated;
    }

    public function setGenerated(bool|string $generated): static
    {
        if (is_string($generated)) {
            $generated = strtoupper($generated) === 'ALWAYS';
        }
        $this->generated = $generated;
        return $this;
    }

    public function getGenerationExpression(): ?string
    {
        return $this->generationExpression;
    }

    public function setGenerationExpression(?string $generationExpression): static
    {
        $this->generationExpression = $generationExpression;
        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): static
    {
        if (!$this->dataType && $this->columnType && $length !== null && $length > 0) {
            $this->dataType = sprintf('%s(%d)', $this->columnType, $length);
        }
        $this->length = $length;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
