<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractNumericScale;
use Pentagonal\Sso\Core\Database\Types\Interfaces\NumericTypeInterface;

class Decimal extends AbstractNumericScale implements NumericTypeInterface
{
    /**
     * @var string
     */
    public const NAME = 'Decimal';

    /**
     * @var string
     */
    protected string $columnType = self::DECIMAL;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 65;

    /**
     * @var ?int
     */
    protected ?int $defaultLength = 10;
}
