<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractText;

class Tinyblob extends AbstractText
{
    /**
     * @var string
     */
    public const NAME = 'Tinyblob';

    /**
     * @var string
     */
    protected string $columnType = self::TINYBLOB;

    /**
     * @param int|null $length
     * @return string
     */
    public function getDeclaration(?int $length = null): string
    {
        return self::TINYBLOB;
    }
}
