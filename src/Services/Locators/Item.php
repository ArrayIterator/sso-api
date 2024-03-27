<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services\Locators;

use Stringable;

readonly class Item implements Stringable
{
    /**
     * @param string $key the name of the item
     * @param mixed $value the value of the item
     */
    public function __construct(
        public string $key,
        public mixed $value
    ) {
    }

    /**
     * @return string the string representation of the object or null
     */
    public function __toString(): string
    {
        return $this->key;
    }
}
