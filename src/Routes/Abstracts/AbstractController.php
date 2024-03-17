<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Abstracts;

use Pentagonal\Sso\Routes\Interfaces\ControllerInterface;
use Psr\Container\ContainerInterface;

abstract class AbstractController implements ControllerInterface
{
    /**
     * @var ?ContainerInterface $container container
     */
    private ?ContainerInterface $container;

    /**
     * @inheritDoc
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }
}
