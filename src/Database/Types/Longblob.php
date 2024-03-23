<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractText;

class Longblob extends AbstractText
{
    /**
     * @var string
     */
    public const NAME = 'Longblob';

    /**
     * @var string
     */
    protected string $columnType = self::LONGBLOB;

    /**
     * @param int|null $length
     * @return string
     */
    public function getDeclaration(?int $length = null): string
    {
        return self::LONGBLOB;
    }
}
