<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Exceptions\Interfaces;

interface HttpExceptionInterface extends SystemExceptionInterface
{
    /**
     * @return int Http Status Code
     */
    public function getStatusCode() : int;
}
