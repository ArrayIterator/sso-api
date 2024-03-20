<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Builder;

use Stringable;

/**
 * Class Expression
 * Take from doctrine Expression
 */
final class Expression
{
    /**
     * @var string the equal operator
     */
    public const EQ  = '=';
    /**
     * @var string the not equal operator
     */
    public const NEQ = '<>';
    /**
     * @var string the less than operator
     */
    public const LT  = '<';
    /**
     * @var string the less than or equal operator
     */
    public const LTE = '<=';
    /**
     * @var string the greater than operator
     */
    public const GT  = '>';
    /**
     * @var string the greater than or equal operator
     */
    public const GTE = '>=';

    /**
     * @param mixed $x
     * @return CompositeExpression
     */
    public function andX(mixed $x = null): CompositeExpression
    {
        return new CompositeExpression(CompositeExpression::TYPE_AND, func_get_args());
    }

    /**
     * @param mixed|null $x
     * @return CompositeExpression
     */
    public function orX(mixed $x = null): CompositeExpression
    {
        return new CompositeExpression(CompositeExpression::TYPE_OR, func_get_args());
    }

    /**
     * Compares two values by operator
     *
     * @param string|Stringable|int|float $x
     * @param string $operator
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function comparison(
        string|Stringable|int|float $x,
        string $operator,
        string|Stringable|int|float $y
    ): string {
        return $x . ' ' . $operator . ' ' . $y;
    }

    /**
     * Compares two values for equality
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function eq(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $this->comparison($x, self::EQ, $y);
    }

    /**
     * Compares two values for inequality
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function neq(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $this->comparison($x, self::NEQ, $y);
    }

    /**
     * Compares two values for less than
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function lt(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $this->comparison($x, self::LT, $y);
    }

    /**
     * Compares two values for less than or equal
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function lte(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $this->comparison($x, self::LTE, $y);
    }

    /**
     * Compares two values for greater than
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function gt(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $this->comparison($x, self::GT, $y);
    }

    /**
     * Compares two values for greater than or equal
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function gte(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $this->comparison($x, self::GTE, $y);
    }

    /**
     * Create query if value is NULL
     *
     * @param string|Stringable|int|float $x
     * @return string
     */
    public function isNull(string|Stringable|int|float $x): string
    {
        return $x . ' IS NULL';
    }

    /**
     * Create query if value is not NULL
     *
     * @param string|Stringable|int|float $x
     * @return string
     */
    public function isNotNull(string|Stringable|int|float $x): string
    {
        return $x . ' IS NOT NULL';
    }

    /**
     * Create query if value is IN
     *
     * @param string|Stringable|int|float $x
     * @param array $y
     * @return string
     */
    public function in(string|Stringable|int|float $x, array $y): string
    {
        return $x . ' IN (' . implode(', ', $y) . ')';
    }

    /**
     * Create query if value is NOT IN
     *
     * @param string|Stringable|int|float $x
     * @param array $y
     * @return string
     */
    public function notIn(string|Stringable|int|float $x, array $y): string
    {
        return $x . ' NOT IN (' . implode(', ', $y) . ')';
    }

    /**
     * Create query if value is LIKE
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function like(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $x . ' LIKE ' . $y;
    }

    /**
     * Create query if value is NOT LIKE
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @return string
     */
    public function notLike(string|Stringable|int|float $x, string|Stringable|int|float $y): string
    {
        return $x . ' NOT LIKE ' . $y;
    }

    /**
     * Create query if value is BETWEEN
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @param string|Stringable|int|float $z
     * @return string
     */
    public function between(
        string|Stringable|int|float $x,
        string|Stringable|int|float $y,
        string|Stringable|int|float $z
    ): string {
        return $x . ' BETWEEN ' . $y . ' AND ' . $z;
    }

    /**
     * Create query if value is NOT BETWEEN
     *
     * @param string|Stringable|int|float $x
     * @param string|Stringable|int|float $y
     * @param string|Stringable|int|float $z
     * @return string
     */
    public function notBetween(
        string|Stringable|int|float $x,
        string|Stringable|int|float $y,
        string|Stringable|int|float $z
    ): string {
        return $x . ' NOT BETWEEN ' . $y . ' AND ' . $z;
    }

    /**
     * Create object
     *
     * @return self
     */
    public static function create() : self
    {
        return new self();
    }
}
