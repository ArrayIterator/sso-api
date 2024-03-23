<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractDateType;

class Timestamp extends AbstractDateType
{
    /**
     * @var string
     */
    public const NAME = 'Timestamp';

    /**
     * @var string
     */
    protected string $columnType = self::TIMESTAMP;

    /**
     * @var string
     */
    protected string $format = 'Y-m-d H:i:s';
}
