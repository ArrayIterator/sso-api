<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractString;

class Varchar extends AbstractString
{
    /**
     * @var string
     */
    public const NAME = 'Varchar';

    /**
     * @var ?int
     */
    protected ?int $maxLength = 65535;

    /**
     * @var string
     */
    protected string $columnType = self::VARCHAR;
}
