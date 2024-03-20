<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Attributes;

use Attribute;
use function is_string;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Route implements Interfaces\AttributeRouteInterface
{
    use Traits\AttributeRouteMethodTrait {
        __construct as private __constructMethod;
    }

    /**
     * @var array<string>
     */
    public array $methods;

    public function __construct(
        array|string $methods,
        string $pattern,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null,
        array $arguments = []
    ) {
        $this->methods = is_string($methods) ? [$methods] : $methods;
        $this->__constructMethod($pattern, $name, $priority, $host, $arguments);
    }

    /**
     * @inheritDoc
     */
    public function getMethods(): array
    {
        return $this->methods;
    }
}
