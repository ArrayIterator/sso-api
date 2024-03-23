<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;

class FloatNumber extends AbstractType
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
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null, ?int $decimalPoint = null) : string
    {
        if ($length === null) {
            return 'FLOAT';
        }

        if ($decimalPoint === null) {
            $decimalPoint = 0;
        }
        return sprintf('FLOAT(%d, %d)', $length, $decimalPoint);
    }
}
