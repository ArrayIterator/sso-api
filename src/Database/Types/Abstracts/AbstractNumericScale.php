<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Abstracts;

use function min;

abstract class AbstractNumericScale extends AbstractType
{
    /**
     * @var string
     */
    protected string $columnType = self::DECIMAL;

    /**
     * @var ?int
     */
    protected ?int $maxLength = null;

    /**
     * @var ?int
     */
    protected ?int $defaultLength = null;

    /**
     * @var int
     */
    protected int $decimalPoint = 0;

    public function getDeclaration(?int $length = null, ?int $decimalPoint = null) : string
    {
        if ($length === null) {
            if ($this->defaultLength === null) {
                return $this->getColumnType();
            }
            return sprintf('%s(%d, %d)', $this->getColumnType(), $this->defaultLength, $this->decimalPoint);
        }

        $decimalPoint = $decimalPoint ?? $this->decimalPoint;
        $decimalPoint = min($decimalPoint, 30);
        if ($this->maxLength !== null) {
            $length = min($length, $this->maxLength);
        }

        return sprintf('%s(%d, %d)', $this->getColumnType(), $length, $decimalPoint);
    }
}
