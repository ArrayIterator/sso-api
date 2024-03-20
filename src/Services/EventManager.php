<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services;

use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use function call_user_func_array;
use function ksort;

class EventManager implements EventManagerInterface
{
    /**
     * @var array<string, array<int, array<int, callable>>> The registered listeners
     */
    private array $listeners = [];

    /**
     * @inheritdoc
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->listeners[$eventName][$priority][] = $listener;
    }

    /**
     * @inheritdoc
     */
    public function removeListener(string $eventName, callable $listener): void
    {
        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $key => $value) {
                if ($listener === $value) {
                    unset($this->listeners[$eventName][$priority][$key]);
                }
            }
            ksort($this->listeners[$eventName]);
        }
    }

    /**
     * @inheritdoc
     */
    public function trigger(string $eventName, ...$arguments): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $listeners) {
            foreach ($listeners as $listener) {
                call_user_func_array($listener, $arguments);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getListeners(string $eventName): array
    {
        return $this->listeners[$eventName] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]);
    }

    /**
     * @inheritdoc
     */
    public function clearListeners(string $eventName): void
    {
        unset($this->listeners[$eventName]);
    }

    /**
     * @inheritdoc
     */
    public function clearAllListeners(): void
    {
        $this->listeners = [];
    }
}
