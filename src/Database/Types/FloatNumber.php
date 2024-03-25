<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractNumericScale;
use Pentagonal\Sso\Core\Database\Types\Interfaces\NumericTypeInterface;

class FloatNumber extends AbstractNumericScale implements NumericTypeInterface
{
    /**
     * @var string
     */
    public const NAME = 'FloatNumber';

    /**
     * @var string
     */
    protected string $columnType = self::FLOAT;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 255;
}
