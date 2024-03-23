<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractDateType;

class Datetime extends AbstractDateType
{
    /**
     * @var string
     */
    public const NAME = 'Datetime';

    /**
     * @var string
     */
    protected string $columnType = self::DATETIME;
}
