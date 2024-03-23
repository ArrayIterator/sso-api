<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;

class Uuid extends AbstractType
{
    /**
     * @var string
     */
    public const NAME = 'Uuid';

    /**
     * @var string
     */
    protected string $columnType = self::UUID;

    /**
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null) : string
    {
        return 'UUID';
    }

    public function databaseValue($value)
    {
        return $value;
    }

    public function value($value)
    {
        if ($value === null) {
            return null;
        }
        return (string) $value;
    }
}
