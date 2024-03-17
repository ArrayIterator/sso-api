<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes\Attributes\Methods;

use Attribute;
use Pentagonal\Sso\Routes\Attributes\Interfaces;
use Pentagonal\Sso\Routes\Attributes\Traits;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Put implements Interfaces\AttributeRouteInterface
{
    use Traits\AttributeRouteMethodTrait;
}
