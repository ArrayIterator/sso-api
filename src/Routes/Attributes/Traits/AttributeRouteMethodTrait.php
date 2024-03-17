<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Attributes\Traits;

use function strtoupper;

trait AttributeRouteMethodTrait
{
    /**
     * @var string $pattern Pattern
     */
    public readonly string $pattern;

    /**
     * @var ?string $name Name
     */
    public readonly ?string $name;

    /**
     * @var ?int $priority Priority
     */
    public readonly ?int $priority;

    /**
     * @var ?string $host Host
     */
    public readonly ?string $host;

    /**
     * @var array route arguments
     */
    public readonly array $arguments;

    /**
     * @param string $pattern
     * @param string|null $name
     * @param int|null $priority
     * @param string|null $host
     * @param array $arguments the route argument
     */
    public function __construct(
        string $pattern,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null,
        array $arguments = []
    ) {
        $this->pattern  = $pattern;
        $this->name     = $name;
        $this->priority = $priority;
        $this->host     = $host;
        $this->arguments = $arguments;
    }

    /**
     * @inheritDoc
     */
    public function getPattern() : string
    {
        return $this->pattern;
    }

    /**
     * @inheritDoc
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getPriority() : ?int
    {
        return $this->priority;
    }

    /**
     * @inheritDoc
     */
    public function getHost() : ?string
    {
        return $this->host;
    }

    public function getArguments() : array
    {
        return $this->arguments;
    }

    /**
     * @return array<string> Method list of route
     */
    public function getMethods(): array
    {
        // get class and get last class name only
        $className = $this::class;
        $className = substr($className, strrpos($className, '\\') + 1);
        return [strtoupper($className)];
    }
}
