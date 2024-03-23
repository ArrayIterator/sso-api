<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;
use Pentagonal\Sso\Core\Utils\Helper\BasicMath;
use function is_numeric;
use function is_string;
use function str_contains;
use function strtolower;

class Numeric extends AbstractType
{
    public const NAME = 'Numeric';

    protected string $columnType = self::BIGINT;

    public function getDeclaration(?int $length = null, ?int $decimalPoint = null) : string
    {
        if ($length === null) {
            return 'BIGINT(20)';
        }

        if ($decimalPoint !== null) {
            return sprintf('DECIMAL(%d, %d)', $length, $decimalPoint);
        }

        return sprintf('BIGINT(%d)', $length);
    }

    /**
     * @param $value
     * @return float|int|numeric-string|null
     */
    public function value($value)
    {
        if (!is_numeric($value)) {
            return null;
        }
        if (is_string($value) && !str_contains($value, '.')) {
            return (int) $value;
        }

        $float = (float) $value;
        if (str_contains(strtolower((string) $float), 'e')) {
            return BasicMath::normalizeNumber($value);
        }

        return $float;
    }

    public function databaseValue($value)
    {
        return $this->value($value);
    }
}
