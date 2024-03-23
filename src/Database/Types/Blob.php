<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractBlob;

class Blob extends AbstractBlob
{
    /**
     * @var string
     */
    public const NAME = 'Blob';

    /**
     * @var string
     */
    protected string $columnType = self::BLOB;

    /**
     * @param int|null $length
     * @return string
     */
    public function getDeclaration(?int $length = null): string
    {
        return self::BLOB;
    }
}
