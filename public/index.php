<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Public;

// doing
use Pentagonal\Sso\Core\Service;
use function define;
use function dirname;

return (function () {
    require dirname(__DIR__) .'/vendor/autoload.php';

    // phpcs:disable PSR1.Files.SideEffects
    define('PUBLIC_PATH', __DIR__);
    define('ROOT_PATH', dirname(__DIR__));

    $service = new Service();
    return $service->run();
})();
