<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Public;

// doing
use function define;

return (function () {

    // phpcs:disable PSR1.Files.SideEffects
    define('PUBLIC_PATH', __DIR__);

    /**
     * @var \Pentagonal\Sso\Core\Service $service
     * @noinspection PhpFullyQualifiedNameUsageInspection
     */
    $service = require __DIR__ . '/../service.php';

    return $service->run();
})();
