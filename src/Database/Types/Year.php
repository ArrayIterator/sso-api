<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractDateType;

class Year extends AbstractDateType
{
    /**
     * @var string
     */
    public const NAME = 'Year';

    /**
     * @var string
     */
    protected string $columnType = self::YEAR;

    /**
     * @var string
     */
    protected string $format = 'Y';
}
