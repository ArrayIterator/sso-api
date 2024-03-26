<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Cache;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Pentagonal\Sso\Core\Cache\Exceptions\InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Throwable;
use function is_int;
use function is_numeric;
use function is_string;

final class CacheItem implements CacheItemInterface
{
    public const RESERVED_CHARACTERS = '{}()/\@:';

    /**
     * @var string
     */
    protected string $key;

    /**
     * @var mixed
     */
    protected mixed $value = null;

    /**
     * @var bool
     */
    protected bool $isHit = false;

    /**
     * @var DateTimeInterface|null
     */
    protected ?DateTimeInterface $expiration = null;

    public static function validateKey($key) : string
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Key must be a string');
        }

        if (empty($key)) {
            throw new InvalidArgumentException('Key cannot be empty');
        }
        if (strpbrk($key, self::RESERVED_CHARACTERS) !== false) {
            throw new InvalidArgumentException(
                'Key cannot contain reserved characters: ' . self::RESERVED_CHARACTERS
            );
        }
        return $key;
    }

    public function getKey() : string
    {
        return $this->key;
    }

    public function get()
    {
        return $this->value;
    }

    public function isHit() : bool
    {
        return $this->isHit;
    }

    public function set($value) : CacheItem
    {
        $this->value = $value;
        return $this;
    }

    public function getExpiration() : ?DateTimeInterface
    {
        return $this->expiration;
    }

    public function expiresAt($expiration) : CacheItem
    {
        if (!$expiration) {
            $this->expiration= null;
            return $this;
        }
        if ($expiration instanceof DateTimeInterface) {
            $this->expiration = $expiration;
            return $this;
        }
        try {
            if (is_int($expiration) || is_numeric($expiration)) {
                $this->expiration = new DateTimeImmutable('@' . ((int)$expiration));
                return $this;
            }
            if (is_string($expiration)) {
                $this->expiration = new DateTimeImmutable($expiration);
                return $this;
            }
        } catch (Throwable) {
            // pass
        }
        return $this;
    }

    public function expiresAfter($time) : CacheItem
    {
        if ($time === null) {
            return $this->expiresAt(null);
        }
        if ($time instanceof DateInterval) {
            return $this->expiresAt((new DateTimeImmutable())->add($time));
        }
        if ($time instanceof DateTimeInterface) {
            $this->expiration = $time;
            return $this;
        }
        if (is_numeric($time)) {
            $time = (int) $time;
            return $this->expiresAt($time + time());
        }
        throw new InvalidArgumentException(
            'Invalid time provided for expiresAfter. Must be an integer, a DateInterval or DateTimeInterface or null'
        );
    }
}
