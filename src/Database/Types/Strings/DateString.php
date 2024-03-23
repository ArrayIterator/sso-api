<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Strings;

use Pentagonal\Sso\Core\Database\Types\Date;

class DateString extends Date
{
    public const NAME = 'Date';

    protected string $columnType = 'date';

    protected bool $stringReturnType = true;
}
