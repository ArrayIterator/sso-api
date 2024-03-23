<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

class Time extends Date
{
    /**
     * @var string
     */
    public const NAME = 'Time';

    /**
     * @var string
     */
    protected string $columnType = self::TIME;

    /**
     * @var string
     */
    protected string $format = 'H:i:s';
}
