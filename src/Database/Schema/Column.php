<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Pentagonal\Sso\Core\Database\Exceptions\TypeException;
use Pentagonal\Sso\Core\Database\Schema\Attributes\Collations;
use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;
use Pentagonal\Sso\Core\Database\Types\Interfaces\TypeInterface;
use Stringable;
use function is_string;

class Column implements Stringable
{
    public const ATTRIBUTE_UNSIGNED = 'unsigned';

    public const ATTRIBUTE_ZEROFILL = 'zerofill';

    public const ATTRIBUTE_ON_UPDATE_CURRENT_TIMESTAMP = 'current_timestamp';

    public const ATTRIBUTE_BINARY = 'binary';

    public const ATTRIBUTE_COMPRESSED = 'compressed';

    /**
     * @var array{
     *     name: string,
     *     type: TypeInterface|null,
     *     length: int|null,
     *     default: mixed,
     *     nullable: bool,
     *     auto_increment: bool,
     *     comment: string|null,
     *     charset: string|null,
     *     collation: string|null,
     *     attributes: string|null,
     *     precision: int|null,
     *     scale: int|null,
     *     ordinal_position: int|null
     * }
     */
    protected array $definitions = [
        'name' => '',
        'type' => null,
        'length' => null,
        'default' => null,
        'nullable' => false,
        'auto_increment' => false,
        'comment' => null,
        'collation' => null,
        'charset' => null,
        'attributes' => null,
        'precision' => null,
        'scale' => null,
        'ordinal_position' => null,
        'column_type' => null,
    ];

    /**
     * Column constructor.
     *
     * @param string $name
     * @param string|TypeInterface $type
     * @param array $attributes
     */
    public function __construct(
        string $name,
        string|TypeInterface $type,
        array $attributes = []
    ) {
        $this->definitions['name'] = trim($name);
        $this->setColumnDataType($type);
        unset($attributes['name']);
        foreach ($attributes as $key => $value) {
            if (! is_string($key)) {
                continue;
            }
            $method = 'set' . str_replace('_', '', $key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * Set column type.
     *
     * @param string|TypeInterface $type The column type.
     * @return $this
     * @see TypeInterface
     */
    private function setColumnDataType(string|TypeInterface $type) : static
    {
        $currentType = is_string($type) ? AbstractType::getType($type) : $type;
        if (! $currentType instanceof TypeInterface) {
            throw new TypeException(
                'Invalid Type Provided'
            );
        }

        $this->definitions['type'] = $currentType;
        return $this;
    }

    /**
     * Set the column length.
     *
     * @param int|null $length The column length.
     * @return $this
     */
    public function setLength(?int $length) : static
    {
        $this->definitions['length'] = $length;
        return $this;
    }

    /**
     * Set the column default.
     *
     * @param mixed $default The column default.
     * @return $this
     */
    public function setDefault(mixed $default) : static
    {
        $this->definitions['default'] = $default;
        return $this;
    }

    /**
     * Set the column nullable.
     *
     * @param bool $nullable The column nullable.
     * @return $this
     */
    public function setNullable(bool $nullable) : static
    {
        $this->definitions['nullable'] = $nullable;
        return $this;
    }

    /**
     * Set the column auto increment.
     *
     * @param bool $autoIncrement The column auto increment.
     * @return $this
     */
    public function setAutoIncrement(bool $autoIncrement) : static
    {
        $this->definitions['auto_increment'] = $autoIncrement;
        return $this;
    }

    /**
     * Set the column type.
     *
     * @param string|null $columnType The column type.
     * @return $this
     */
    public function setColumnType(?string $columnType) : static
    {
        $this->definitions['column_type'] = $columnType;
        return $this;
    }

    /**
     * Set the column comment.
     *
     * @param string|null $comment The column comment.
     * @return $this
     */
    public function setComment(?string $comment) : static
    {
        $this->definitions['comment'] = $comment;
        return $this;
    }

    /**
     * Set the column charset.
     *
     * @param string|null $charset The column charset.
     * @return $this
     */
    public function setCharset(?string $charset) : static
    {
        $this->definitions['charset'] = $charset
            ? Collations::normalizeCharset($charset)
            : null;
        return $this;
    }

    /**
     * Set the column collation.
     *
     * @param string|null $collation The column collation.
     * @return $this
     */
    public function setCollation(?string $collation) : static
    {
        $this->definitions['collation'] = $collation
            ? Collations::normalizeCollation($collation)
            : null;
        return $this;
    }

    /**
     * Set the column precision.
     *
     * @param int|null $precision The column precision.
     * @return $this
     */
    public function setPrecision(?int $precision) : static
    {
        $this->definitions['precision'] = $precision;
        return $this;
    }

    /**
     * Set the column scale.
     *
     * @param int|null $scale The column scale.
     * @return $this
     */
    public function setScale(?int $scale) : static
    {
        $this->definitions['scale'] = $scale;
        return $this;
    }

    /**
     * Set the column ordinal position.
     *
     * @param int|null $ordinalPosition The column ordinal position.
     * @return $this
     */
    public function setOrdinalPosition(?int $ordinalPosition) : static
    {
        $this->definitions['ordinal_position'] = $ordinalPosition;
        return $this;
    }

    /**
     * Reset attributes
     *
     * @return $this
     */
    public function resetAttributes() : static
    {
        $this->definitions['attributes'] = null;
        return $this;
    }

    /**
     * Set attribute on update current timestamp
     *
     * @return $this
     */
    public function setOnUpdateCurrentTimestamp() : static
    {
        $this->definitions['attributes'] = static::ATTRIBUTE_ON_UPDATE_CURRENT_TIMESTAMP;
        return $this;
    }

    public function setUnsigned() : static
    {
        $this->definitions['attributes'] = static::ATTRIBUTE_UNSIGNED;
        return $this;
    }

    /**
     * Set attribute zerofill
     *
     * @return $this
     */
    public function setZerofill() : static
    {
        $this->definitions['attributes'] = static::ATTRIBUTE_ZEROFILL;
        return $this;
    }

    /**
     * Set attribute binary
     *
     * @return $this
     */
    public function setBinary() : static
    {
        $this->definitions['attributes'] = static::ATTRIBUTE_BINARY;
        return $this;
    }

    /**
     * Set attribute compressed
     *
     * @return $this
     */
    public function setCompressed() : static
    {
        $this->definitions['attributes'] = static::ATTRIBUTE_COMPRESSED;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->definitions['name'];
    }

    /**
     * @return TypeInterface|null
     */
    public function getType() : ?TypeInterface
    {
        return $this->definitions['type'];
    }

    /**
     * @return ?string column type
     */
    public function getColumnType() : ?string
    {
        return $this->definitions['column_type'];
    }
    /**
     * @return ?int column length
     */
    public function getLength() : ?int
    {
        return $this->definitions['length'];
    }

    /**
     * @return mixed column default
     */
    public function getDefault() : mixed
    {
        return $this->definitions['default'];
    }

    /**
     * @return bool column nullable
     */
    public function isNullable() : bool
    {
        return $this->definitions['nullable'];
    }

    /**
     * @return bool column auto increment
     */
    public function isAutoIncrement() : bool
    {
        return $this->definitions['auto_increment'];
    }

    /**
     * @return ?string column comment
     */
    public function getComment() : ?string
    {
        return $this->definitions['comment'];
    }

    public function getCharset() : ?string
    {
        return $this->definitions['charset'];
    }

    /**
     * @return ?string column collation
     */
    public function getCollation() : ?string
    {
        return $this->definitions['collation'];
    }

    /**
     * @return bool column unsigned
     */
    public function isUnsigned() : bool
    {
        return $this->definitions['attributes'] === static::ATTRIBUTE_UNSIGNED;
    }

    /**
     * @return bool column zerofill
     */
    public function isZerofill() : bool
    {
        return $this->definitions['attributes'] === static::ATTRIBUTE_ZEROFILL;
    }
    public function isBinary() : bool
    {
        return $this->definitions['attributes'] === static::ATTRIBUTE_BINARY;
    }

    public function isCompressed() : bool
    {
        return $this->definitions['attributes'] === static::ATTRIBUTE_COMPRESSED;
    }

    public function isOnUpdateCurrentTimestamp() : bool
    {
        return $this->definitions['attributes'] === static::ATTRIBUTE_ON_UPDATE_CURRENT_TIMESTAMP;
    }

    /**
     * @return ?int column precision
     */
    public function getPrecision() : ?int
    {
        return $this->definitions['precision'];
    }

    /**
     * @return ?int column scale
     */
    public function getScale() : ?int
    {
        return $this->definitions['scale'];
    }

    /**
     * @return ?int
     */
    public function getOrdinalPosition() : ?int
    {
        return $this->definitions['ordinal_position'];
    }

    /**
     * @return array{
     *     name: string,
     *     type: TypeInterface|null,
     *     length: int|null,
     *     default: mixed,
     *     nullable: bool,
     *     auto_increment: bool,
     *     comment: string|null,
     *     charset: string|null,
     *     collation: string|null,
     *     attributes: string|null,
     *     precision: int|null,
     *     scale: int|null,
     *     ordinal_position: int|null
     *  }
     */
    public function getDefinitions() : array
    {
        return $this->definitions;
    }

    /**
     * @return string The column name.
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
