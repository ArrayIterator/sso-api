<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;

class Double extends AbstractType
{
    /**
     * @var string
     */
    public const NAME = 'Double';

    /**
     * @var string
     */
    protected string $columnType = self::DOUBLE;

    /**
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null, ?int $decimalPoint = null) : string
    {
        if ($length === null) {
            return 'DOUBLE';
        }
        if ($decimalPoint === null) {
            $decimalPoint = 0;
        }
        return sprintf('DOUBLE(%d, %d)', $length, $decimalPoint);
    }
}
