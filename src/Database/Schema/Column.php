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
    /**
     * @var array{
     *     name: string,
     *     type: TypeInterface|null,
     *     length: int|null,
     *     default: mixed,
     *     nullable: bool,
     *     auto_increment: bool,
     *     comment: string|null,
     *     collation: string|null,
     *     unsigned: bool,
     *     zerofill: bool,
     *     precision: int|null,
     *     scale: int|null,
     *     on_delete: string|null,
     *     on_update: string|null,
     *     ordinal_position: int|null
     * }
     */
    protected array $attributes = [
        'name' => '',
        'type' => null,
        'length' => null,
        'default' => null,
        'nullable' => false,
        'auto_increment' => false,
        'comment' => null,
        'collation' => null,
        'unsigned' => false,
        'zerofill' => false,
        'precision' => null,
        'scale' => null,
        'on_delete' => null,
        'on_update' => null,
        'ordinal_position' => null,
    ];

    /**
     * Column constructor.
     *
     * @param string $name
     * @param array $attributes
     */
    public function __construct(
        string $name,
        array $attributes = []
    ) {
        $this->attributes['name'] = trim($name);
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
    public function setType(string|TypeInterface $type) : static
    {
        $currentType = is_string($type) ? AbstractType::getType($type) : $type;
        if (! $currentType instanceof TypeInterface) {
            throw new TypeException(
                'Invalid Type Provided'
            );
        }
        $this->attributes['type'] = $type;
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
        $this->attributes['length'] = $length;
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
        $this->attributes['default'] = $default;
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
        $this->attributes['nullable'] = $nullable;
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
        $this->attributes['auto_increment'] = $autoIncrement;
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
        $this->attributes['comment'] = $comment;
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
        $this->attributes['collation'] = $collation
            ? Collations::normalizeCollation($collation)
            : null;
        return $this;
    }

    public function setUnsigned(bool $unsigned) : static
    {
        $this->attributes['unsigned'] = $unsigned;
        return $this;
    }

    /**
     * Set the column zerofill.
     *
     * @param bool $zerofill The column zerofill.
     * @return $this
     */
    public function setZerofill(bool $zerofill) : static
    {
        $this->attributes['zerofill'] = $zerofill;
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
        $this->attributes['precision'] = $precision;
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
        $this->attributes['scale'] = $scale;
        return $this;
    }

    /**
     * Set the column on delete.
     *
     * @param string|null $onDelete The column on delete.
     * @return $this
     */
    public function setOnDelete(?string $onDelete) : static
    {
        $this->attributes['on_delete'] = $onDelete;
        return $this;
    }

    /**
     * Set the column on update.
     *
     * @param string|null $onUpdate The column on update.
     * @return $this
     */
    public function setOnUpdate(?string $onUpdate) : static
    {
        $this->attributes['on_update'] = $onUpdate;
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
        $this->attributes['ordinal_position'] = $ordinalPosition;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->attributes['name'];
    }

    /**
     * @return TypeInterface|null
     */
    public function getType() : ?TypeInterface
    {
        return $this->attributes['type'];
    }

    /**
     * @return ?int column length
     */
    public function getLength() : ?int
    {
        return $this->attributes['length'];
    }

    /**
     * @return mixed column default
     */
    public function getDefault() : mixed
    {
        return $this->attributes['default'];
    }

    /**
     * @return bool column nullable
     */
    public function isNullable() : bool
    {
        return $this->attributes['nullable'];
    }

    /**
     * @return bool column auto increment
     */
    public function isAutoIncrement() : bool
    {
        return $this->attributes['auto_increment'];
    }

    /**
     * @return ?string column comment
     */
    public function getComment() : ?string
    {
        return $this->attributes['comment'];
    }

    /**
     * @return ?string column collation
     */
    public function getCollation() : ?string
    {
        return $this->attributes['collation'];
    }

    /**
     * @return bool column unsigned
     */
    public function isUnsigned() : bool
    {
        return $this->attributes['unsigned'];
    }

    /**
     * @return bool column zerofill
     */
    public function isZerofill() : bool
    {
        return $this->attributes['zerofill'];
    }

    /**
     * @return ?int column precision
     */
    public function getPrecision() : ?int
    {
        return $this->attributes['precision'];
    }

    /**
     * @return ?int column scale
     */
    public function getScale() : ?int
    {
        return $this->attributes['scale'];
    }

    /**
     * @return ?string column on delete
     */
    public function getOnDelete() : ?string
    {
        return $this->attributes['on_delete'];
    }

    /**
     * @return ?string column on update
     */
    public function getOnUpdate() : ?string
    {
        return $this->attributes['on_update'];
    }

    /**
     * @return ?int
     */
    public function getOrdinalPosition() : ?int
    {
        return $this->attributes['ordinal_position'];
    }

    /**
     * @return array{
     *      name: string,
     *      type: TypeInterface|null,
     *      length: int|null,
     *      default: mixed,
     *      nullable: bool,
     *      auto_increment: bool,
     *      comment: string|null,
     *      collation: string|null,
     *      unsigned: bool,
     *      zerofill: bool,
     *      precision: int|null,
     *      scale: int|null,
     *      on_delete: string|null,
     *      on_update: string|null,
     *      ordinal_position: int|null
     *  }
     */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /**
     * @return string The column name.
     */
    public function __toString(): string
    {
        return $this->getName();
    }
}
