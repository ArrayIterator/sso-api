<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Abstracts;

use function is_bool;
use function sprintf;

abstract class AbstractBinary extends AbstractType
{
    protected string $columnType = self::VARBINARY;

    /**
     * @var int
     */
    protected int $defaultLength = 255;

    protected ?int $maxLength  = null;

    /**
     * @param ?int $length
     * @return string
     */
    public function getDeclaration(?int $length = null): string
    {
        $lengths = [
            self::BINARY => 255,
            self::VARBINARY => 65535,
        ];

        if ($length === null) {
            return sprintf('%s(%d)', $this->columnType, $this->defaultLength);
        }
        if ($this->maxLength !== null) {
            $length = min($length, $this->maxLength);
            return sprintf('%s(%d)', $this->columnType, $length);
        }

        $currentType = null;
        foreach ($lengths as $type => $max) {
            if ($length <= $max) {
                $currentType = $type;
            }
        }

        if ($currentType === null) {
            $length = min($length, $lengths[self::VARBINARY]);
            return sprintf('%s(%d)', self::VARBINARY, $length);
        }

        $length = min($length, $lengths[$currentType]);
        return sprintf('%s(%d)', $currentType, $length);
    }

    public function value($value) : ?string
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function databaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }
        return (string) $value;
    }
}
