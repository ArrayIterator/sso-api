<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractString;

class Char extends AbstractString
{
    /**
     * @var string
     */
    public const NAME = 'Char';

    /**
     * @var string
     */
    protected string $columnType = self::CHAR;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 255;

    /**
     * @var int
     */
    protected int $defaultLength = 1;
}
