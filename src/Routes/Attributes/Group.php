<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Attributes;

use Attribute;

/**
 * Group pattern is not support regex
 *
 * @psalm-immutable
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Group
{
    public function __construct(
        public string $pattern
    ) {
    }

    /**
     * @return string Pattern
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }
}
