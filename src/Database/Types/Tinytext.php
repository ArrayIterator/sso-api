<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractText;

class Tinytext extends AbstractText
{
    /**
     * @var string
     */
    public const NAME = 'Tinytext';

    /**
     * @var string
     */
    protected string $columnType = self::TINYTEXT;

    /**
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null): string
    {
        return self::TINYTEXT;
    }
}
