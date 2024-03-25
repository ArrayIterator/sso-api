<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Builder;

use Countable;
use Pentagonal\Sso\Core\Exceptions\InvalidArgumentException;
use Stringable;
use function in_array;
use function sprintf;

/**
 * Class CompositeExpression
 * Take from Doctrine Expression
 */
class CompositeExpression implements Countable, Stringable
{
    /**
     * Constant for an AND composite expression.
     */
    public const TYPE_AND = 'AND';

    /**
     * Constant for an OR composite expression.
     */
    public const TYPE_OR = 'OR';

    /**
     * @var string type
     */
    private string $type;

    /**
     * @var array parts
     */
    private array $parts = [];

    /**
     * CompositeExpression constructor.
     *
     * @param string $type
     * @param array $parts
     */
    public function __construct(string $type, array $parts = [])
    {
        if (!in_array($type, [self::TYPE_AND, self::TYPE_OR], true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid type "%s" for CompositeExpression',
                    $type
                )
            );
        }

        $this->type = $type;
        $this->addMultiple($parts);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->parts);
    }

    /**
     * Add part
     *
     * @param Stringable|string|int|float $part
     *
     * @return $this
     */
    public function add(
        Stringable|string|int|float $part
    ): self {
        if (!empty($part) || ($part instanceof self && count($part->parts) === 0)) {
            $this->parts[] = $part;
        }

        return $this;
    }

    /**
     * Add multiple parts
     *
     * @param array $parts
     */
    public function addMultiple(array $parts): void
    {
        foreach ($parts as $part) {
            $this->add($part);
        }
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        if (count($this->parts) === 0) {
            return '';
        }
        if (count($this->parts) === 1) {
            return (string) $this->parts[0];
        }
        return '(' . implode(') ' . $this->type . ' (', $this->parts) . ')';
    }

    /**
     * @return string type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array parts
     */
    public function getParts(): array
    {
        return $this->parts;
    }
}
