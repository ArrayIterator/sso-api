<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Interfaces;

interface RouteGroupInterface
{
    public function __construct(
        RouterInterface $router,
        string $pattern,
        callable $callback,
        ?RouteGroupInterface $previousGroup = null
    );

    /**
     * @return string pattern
     */
    public function getPattern() : string;

    /**
     * Get previous group
     *
     * @return RouteGroupInterface|null
     */
    public function getPreviousGroup() : ?RouteGroupInterface;

    /**
     * Process group
     */
    public function dispatch();
}
