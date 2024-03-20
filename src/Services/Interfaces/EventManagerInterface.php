<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Services\Interfaces;

interface EventManagerInterface
{
    /**
     * Add a listener to an event.
     *
     * @param string   $eventName The name of the event to listen to
     * @param callable $listener  The listener to call when the event is dispatched
     * @param int      $priority  The higher this value,
     *  the earlier an event listener will be triggered in the chain (defaults to 0)
     */
    public function addListener(string $eventName, callable $listener, int $priority = 0): void;

    /**
     * Remove a listener from an event.
     *
     * @param string   $eventName The name of the event to remove the listener from
     * @param callable $listener  The listener to remove
     */
    public function removeListener(string $eventName, callable $listener): void;

    /**
     * Dispatch an event.
     *
     * @param string $eventName The name of the event to dispatch
     * @param array  $arguments The arguments to pass to the event listeners
     */
    public function trigger(string $eventName, ...$arguments): void;

    /**
     * Get the listeners of a specific event or all listeners.
     *
     * @param string $eventName The name of the event
     *
     * @return array The event listeners for the specified event, or all event listeners
     */
    public function getListeners(string $eventName): array;

    /**
     * Check if an event has any registered listeners.
     *
     * @param string $eventName The name of the event
     *
     * @return bool true if the specified event has any listeners, false otherwise
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Remove all listeners for an event.
     *
     * @param string $eventName The name of the event
     */
    public function clearListeners(string $eventName): void;

    /**
     * Remove all listeners.
     */
    public function clearAllListeners(): void;
}
