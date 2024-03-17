<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Interfaces;

use Psr\Container\ContainerInterface;

interface ControllerInterface
{
    /**
     * @param ?ContainerInterface $container
     */
    public function __construct(?ContainerInterface $container = null);

    /**
     * @return ?ContainerInterface
     */
    public function getContainer() : ?ContainerInterface;
}
