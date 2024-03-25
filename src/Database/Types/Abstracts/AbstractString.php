<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Abstracts;

use function is_bool;
use function sprintf;

class AbstractString extends AbstractType
{
    /**
     * @var string
     */
    protected string $columnType = self::VARCHAR;

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
     * @inheritdoc
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
            self::CHAR => 255,
            self::VARCHAR => 2048, // override
            // self::TINYTEXT => 255,
            self::TEXT => 65535,
            self::MEDIUMTEXT => 16777215,
            self::LONGTEXT => 4294967295,
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

    /**
     * @inheritdoc
     */

    public function databaseValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        return (string) $value;
    }
}
