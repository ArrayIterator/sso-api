<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes\Attributes\Interfaces;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
interface AttributeRouteInterface
{
    /**
     * Get Methods
     *
     * @return array<string>
     */
    public function getMethods() : array;

    /**
     * Get Pattern
     * @return string
     */
    public function getPattern() : string;

    /**
     * Get Name
     * @return ?string
     */
    public function getName() : ?string;

    /**
     * Get Priority
     * @return ?int
     */
    public function getPriority() : ?int;

    /**
     * Get Host
     * @return ?string
     */
    public function getHost() : ?string;

    /**
     * @return array<string, mixed>
     */
    public function getArguments() : array;
}
