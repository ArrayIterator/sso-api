<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractInteger;
use Pentagonal\Sso\Core\Database\Types\Interfaces\NumericTypeInterface;

class Bigint extends AbstractInteger implements NumericTypeInterface
{
    /**
     * @var string
     */
    public const NAME = 'Bigint';

    /**
     * @var string
     */
    protected string $columnType = self::BIGINT;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 255;

    /**
     * @var int
     */
    protected int $defaultLength = 20;
}
