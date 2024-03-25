<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

class Double extends FloatNumber
{
    /**
     * @var string
     */
    public const NAME = 'Double';

    /**
     * @var string
     */
    protected string $columnType = self::DOUBLE;
}
