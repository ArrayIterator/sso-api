<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Abstracts;

use DateTimeImmutable;
use DateTimeInterface;
use Pentagonal\Sso\Core\Database\Exceptions\TypeException;
use Pentagonal\Sso\Core\Database\Types\FloatNumber;
use Pentagonal\Sso\Core\Database\Types\Integer;
use Pentagonal\Sso\Core\Database\Types\Interfaces\TypeInterface;
use Pentagonal\Sso\Core\Database\Types\Strings;
use function array_pop;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_a;
use function is_array;
use function is_string;
use function sprintf;
use function strrpos;
use function strtolower;
use function substr;
use function ucwords;

abstract class AbstractType implements TypeInterface
{
    public const NAME = '';

    private static array $types = [
        self::FLOAT => FloatNumber::class,
        self::INTEGER => Integer::class,
        self::STRING => Strings::class,
    ];

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $columnType;

    /**
     * @var bool
     */
    protected bool $lengthSupported;

    /**
     * @var string
     */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /**
     * @var ?string $namespace
     */
    private static ?string $namespace = null;

    /**
     * @var class-string<DateTimeInterface>
     */
    protected string $dateClassName = DateTimeImmutable::class;

    public function __construct()
    {
        if (!isset($this->name)) {
            $name = static::NAME;
            if ($name) {
                $this->name = $name;
            } else {
                // get last name
                $name = get_class($this);
                $name = substr($name, strrpos($name, '\\') + 1);
                $this->name = substr($name, strrpos($name, '\\') + 1);
            }
        }

        if (!isset($this->columnType)) {
            $name = strtolower($this->getName());
            if (in_array($name, static::TYPES)) {
                $this->columnType = $name;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function getDateClassName(): string
    {
        return $this->dateClassName;
    }

    /**
     * Set Date Class Name
     *
     * @param string $dateClassName
     */
    public function setDateClassName(string $dateClassName): void
    {
        if (!is_a($dateClassName, DateTimeInterface::class, true)) {
            throw new TypeException(
                sprintf(
                    'Date Class Name must be instance of %s',
                    DateTimeInterface::class
                )
            );
        }

        $this->dateClassName = $dateClassName;
    }

    /**
     * @inheritdoc
     */
    public function isLengthSupported(): bool
    {
        if (!isset($this->lengthSupported)) {
            $this->lengthSupported = isset(self::LENGTH_SUPPORTED[$this->getColumnType()]);
        }

        return $this->lengthSupported;
    }

    /**
     * @inheritdoc
     */
    public function value($value)
    {
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function databaseValue($value)
    {
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getColumnType(): string
    {
        return strtolower($this->columnType);
    }

    /**
     * @inheritdoc
     */
    public function getDeclaration(?int $length = null): string
    {
        $declaration = $this->getColumnType();
        if (!$this->isLengthSupported()) {
            return $declaration;
        }

        $definitions = self::LENGTH_SUPPORTED[$declaration]??null;
        if ($definitions === null) {
            return $declaration;
        }
        $minLength = $definitions['min'] ?? null;
        $maxLength = $definitions['max'] ?? null;
        $defaultLength = $definitions['default'] ?? null;
        if ($length === null) {
            $length = $defaultLength;
        }
        if (is_array($length)) {
            /**
             * @var int[] $length
             */
            return !empty($length)
                ? sprintf(
                    '%s(%s)',
                    $declaration,
                    implode(',', $length)
                )
                : $declaration;
        }
        if ($length === null) {
            return $declaration;
        }
        if ($minLength !== null && $length < $minLength) {
            $length = $minLength;
        }
        if ($maxLength !== null && $length > $maxLength) {
            $length = $maxLength;
        }
        return sprintf('%s(%d)', $declaration, $length);
    }

    public static function addType(TypeInterface $type): void
    {
        static::$types[strtolower($type->getName())] = $type;
    }

    public static function hasType(string $name): bool
    {
        return isset(static::$types[$name]);
    }

    public static function getType(string $name): ?TypeInterface
    {
        $type = static::$types[$name] ?? null;
        if (!$type) {
            // get parent namespace
            if (self::$namespace === null) {
                $namespace = explode('\\', __CLASS__);
                array_pop($namespace);
                self::$namespace = implode('\\', $namespace);
            }

            $namespace = explode('\\', __NAMESPACE__);
            array_pop($namespace);
            $namespace = implode('\\', $namespace);
            $className = $namespace .'\\'.ucwords(strtolower($name), ' \\');
            if (is_a($className, TypeInterface::class, true)) {
                $type = new $className();
                static::$types[$name] = $type;
            }
        }

        if (!$type) {
            return null;
        }
        if (is_string($type)) {
            $type = new $type();
            static::$types[$name] = $type;
        }
        return $type;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
