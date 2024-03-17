<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

interface RouterInterface extends RouteMethodInterface, MiddlewareInterface
{
    /**
     * RouterInterface constructor.
     *
     * @param RoutesInterface $routeCollections
     */
    public function __construct(
        RoutesInterface $routeCollections
    );

    /**
     * Get Routes
     *
     * @return RoutesInterface
     */
    public function getRoutes() : RoutesInterface;

    /**
     * Get base path
     * @return string
     */
    public function getBasePath() : string;

    /**
     * Set base path
     *
     * @param string $basePath
     * @return RouterInterface
     */
    public function setBasePath(string $basePath) : RouterInterface;

    /**
     * Get current group
     * @return ?RouteGroupInterface
     */
    public function getCurrentGroup() : ?RouteGroupInterface;
}
