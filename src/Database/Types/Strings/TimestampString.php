<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Strings;

use Pentagonal\Sso\Core\Database\Types\Timestamp;

class TimestampString extends Timestamp
{
    public const NAME = 'TimeStampString';

    protected bool $stringReturnType = true;
}
