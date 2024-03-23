<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractInteger;

class Integer extends AbstractInteger
{
    /**
     * @var string
     */
    public const NAME = 'Integer';

    /**
     * @var string
     */
    protected string $columnType = self::INT;

    /**
     * @var ?int
     */
    protected ?int $maxLength = 11;
}
