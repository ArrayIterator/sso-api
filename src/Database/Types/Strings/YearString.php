<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Strings;

use Pentagonal\Sso\Core\Database\Types\Date;

class YearString extends Date
{
    public const NAME = 'YearString';

    protected string $columnType = 'year';

    protected bool $stringReturnType = true;
}
