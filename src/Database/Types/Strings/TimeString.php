<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Strings;

use Pentagonal\Sso\Core\Database\Types\Time;

class TimeString extends Time
{
    public const NAME = 'TimeString';

    protected bool $stringReturnType = true;
}
