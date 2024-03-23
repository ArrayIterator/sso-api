<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractInteger;

class Mediumint extends AbstractInteger
{
    /**
     * @var string
     */
    public const NAME = 'Mediumint';

    /**
     * @var string
     */
    protected string $columnType = self::MEDIUMINT;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 255;

    /**
     * @var int
     */
    protected int $defaultLength = 9;
}
