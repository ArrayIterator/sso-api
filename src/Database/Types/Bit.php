<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;

class Bit extends AbstractType
{
    /**
     * @var string
     */
    public const NAME = 'Bit';

    /**
     * @var string
     */
    protected string $columnType = self::BIT;

    /**
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null): string
    {
        if ($length === null || $length < 0) {
            $length = 1;
        }
        if ($length > 64) {
            $length = 64;
        }
        return 'BIT(' . $length . ')';
    }
}
