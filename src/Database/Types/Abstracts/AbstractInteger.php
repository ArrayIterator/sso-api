<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Abstracts;

use Pentagonal\Sso\Core\Database\Types\Interfaces\NumericTypeInterface;
use function min;
use function sprintf;

abstract class AbstractInteger extends AbstractType implements NumericTypeInterface
{
    /**
     * @var string
     */
    protected string $columnType = self::INT;

    /**
     * @var ?int
     */
    protected ?int $maxLength = null;
    /**
     * @var int
     */
    protected int $defaultLength = 255;

    /**
     * @var bool
     */
    protected bool $lengthSupported = true;

    /**
     * @param ?int $length
     * @return string
     */
    public function getDeclaration(?int $length = null): string
    {
        if ($length === null) {
            return sprintf('%s(%d)', $this->getColumnType(), $this->defaultLength);
        }

        if ($this->maxLength !== null) {
            $length = min($length, $this->maxLength);
            return sprintf('%s(%d)', $this->getColumnType(), $length);
        }

        $lengths = [
            self::TINYINT => 3,
            self::SMALLINT => 5,
            self::MEDIUMINT => 8,
            self::INT => 11,
            self::BIGINT => 20,
        ];

        $currentType = null;
        foreach ($lengths as $type => $max) {
            if ($length <= $max) {
                $length = $max;
                $currentType = $type;
                break;
            }
        }

        if ($currentType === null) {
            $currentType = self::LONGTEXT;
        }

        if ($length > 2048) {
            return $currentType;
        }

        return sprintf('%s(%d)', $currentType, $length);
    }

    public function value($value) : ?int
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * @param mixed $value
     * @return ?int
     */
    public function databaseValue($value) : ?int
    {
        if ($value === null) {
            return null;
        }
        return (int) $value;
    }
}
