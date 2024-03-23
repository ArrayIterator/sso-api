<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Strings;

use Pentagonal\Sso\Core\Database\Types\Datetime;

class DatetimeString extends Datetime
{
    public const NAME = 'DatetimeString';

    protected bool $stringReturnType = true;
}
