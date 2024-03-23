<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;

class Decimal extends AbstractType
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
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null, ?int $decimalPoint = null) : string
    {
        if ($length === null) {
            return 'DECIMAL(10, 0)';
        }
        if ($decimalPoint === null) {
            $decimalPoint = 0;
        }
        return sprintf('DOUBLE(%d, %d)', $length, $decimalPoint);
    }
}
