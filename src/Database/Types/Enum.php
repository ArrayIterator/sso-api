<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Exceptions\TypeException;
use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;
use function addslashes;
use function array_map;
use function implode;
use function is_array;

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

    public function getDeclaration(?int $length = null, ?array $values = null): string
    {
        if (!is_array($values) || count($values) < 1) {
            throw new TypeException(
                'Values must be an array and not empty'
            );
        }

        $values = array_map(function ($value) {
            return "'" . addslashes($value) . "'";
        }, $values);

        return 'ENUM(' . implode(',', $values) . ')';
    }
}
