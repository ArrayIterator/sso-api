<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Exceptions\TypeException;
use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;
use Stringable;
use function addslashes;
use function array_map;
use function implode;

class Enum extends AbstractType
{
    /**
     * @var string
     */
    public const NAME = 'Enum';

    /**
     * @var string
     */
    protected string $columnType = self::ENUM;

    /**
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null, int|float|string|Stringable ...$values): string
    {
        if (count($values) < 1) {
            throw new TypeException(
                'Values must be an array and not empty'
            );
        }

        $values = array_map(function ($value) {
            $value = (string) $value;
            return "'" . addslashes($value) . "'";
        }, $values);

        return 'ENUM(' . implode(',', $values) . ')';
    }
}
