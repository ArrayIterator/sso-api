<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Abstracts;

use DateTimeInterface;
use Throwable;
use function date;
use function is_numeric;
use function is_string;

abstract class AbstractDateType extends AbstractType
{
    /**
     * @var bool true if return type is string
     */
    protected bool $stringReturnType = false;

    /**
     * Determine if return type is string
     */
    public function isStringReturnType(): bool
    {
        return $this->stringReturnType;
    }

    /**
     * @inheritDoc
     */
    public function value($value)
    {
        if ($value instanceof DateTimeInterface) {
            return $this->stringReturnType
                ? $value->format($this->getDateFormat())
                : new ($this->dateClassName)($value->format('c'));
        }

        if (($num = is_numeric($value)) || is_string($value)) {
            if ($num) {
                $date = date($this->getDateFormat(), (int) $value);
                return $this->stringReturnType
                    ? $date
                    : new ($this->dateClassName)($date);
            }
            try {
                $date = new ($this->dateClassName)($value);
                return $this->stringReturnType
                    ? $date->format($this->getDateFormat())
                    : $date;
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function databaseValue($value)
    {
        if ($value === null) {
            return null;
        }
        $value = $this->value($value);
        if ($value instanceof DateTimeInterface) {
            return $value->format($this->getDateFormat());
        }

        return null;
    }
}
