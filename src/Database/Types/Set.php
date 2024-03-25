<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Exceptions\TypeException;
use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;
use Stringable;

class Set extends AbstractType
{
    public const NAME = 'Set';

    protected string $columnType = self::SET;

    /**
     * Get Declaration
     *
     * @param int|null $length
     * @param ?array $values array of values
     * @return string
     * @throws TypeException
     */
    public function getDeclaration(?int $length = null, int|float|string|Stringable ...$values): string
    {
        if (!is_array($values) || count($values) < 1) {
            throw new TypeException(
                'Values must be an array and not empty'
            );
        }

        $values = array_map(function ($value) {
            $value = (string) $value;
            return "'" . addslashes($value) . "'";
        }, $values);

        return 'SET(' . implode(',', $values) . ')';
    }
}
