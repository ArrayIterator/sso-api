<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractText;

class Text extends AbstractText
{
    /**
     * @var string
     */
    public const NAME = 'Text';

    /**
     * @var string
     */
    protected string $columnType = self::TEXT;

    /**
     * @param int|null $length
     * @return string
     */
    public function getDeclaration(?int $length = null): string
    {
        return self::TEXT;
    }
}
