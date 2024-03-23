<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractDateType;

class Date extends AbstractDateType
{
    /**
     * @var string
     */
    public const NAME = 'Date';

    /**
     * @var string
     */
    protected string $columnType = self::DATE;

    /**
     * @var string
     */
    protected string $dateFormat = 'Y-m-d';
}
