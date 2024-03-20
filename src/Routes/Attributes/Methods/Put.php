<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Attributes\Methods;

use Attribute;
use Pentagonal\Sso\Core\Routes\Attributes\Interfaces;
use Pentagonal\Sso\Core\Routes\Attributes\Traits;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Put implements Interfaces\AttributeRouteInterface
{
    use Traits\AttributeRouteMethodTrait;
}
