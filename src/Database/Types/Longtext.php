<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractText;

class Longtext extends AbstractText
{
    /**
     * @var string
     */
    public const NAME = 'Longtext';

    /**
     * @var string
     */
    protected string $columnType = self::LONGTEXT;

    /**
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null): string
    {
        return self::LONGTEXT;
    }
}
