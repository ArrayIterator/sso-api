<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractBinary;

class Varbinary extends AbstractBinary
{
    /**
     * @var string
     */
    public const NAME = 'Varbinary';

    /**
     * @var string
     */
    protected string $columnType = self::VARBINARY;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 65535;

    /**
     * @var int
     */
    protected int $defaultLength = 255;
}
