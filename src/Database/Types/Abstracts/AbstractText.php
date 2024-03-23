<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Abstracts;

use function is_bool;

abstract class AbstractText extends AbstractType
{
    protected string $columnType = self::TEXT;

    /**
     * @param ?int $length
     * @return string
     */
    public function getDeclaration(?int $length = null): string
    {
        $lengths = [
            self::TINYTEXT => 255,
            self::TEXT => 65535,
            self::MEDIUMTEXT => 16777215,
            self::LONGTEXT => 4294967295,
        ];

        if ($length === null) {
            return self::TEXT;
        }

        foreach ($lengths as $type => $max) {
            if ($length <= $max) {
                return $type;
            }
        }

        return self::TEXT;
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
