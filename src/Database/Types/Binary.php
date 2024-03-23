<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractBinary;

class Binary extends AbstractBinary
{
    /**
     * @var string
     */
    public const NAME = 'Binary';

    /**
     * @var string
     */
    protected string $columnType = self::BINARY;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 255;

    /**
     * @var int
     */
    protected int $defaultLength = 1;
}
