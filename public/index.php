<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Public;

// doing
use Pentagonal\Sso\Core\Service;
use function define;
use function dirname;

return (function () {

    // phpcs:disable PSR1.Files.SideEffects

    define('ROOT_PATH', dirname(__DIR__));
    define('APP_PATH', \ROOT_PATH . '/app');
    define('PUBLIC_PATH', __DIR__);

    require \ROOT_PATH .'/vendor/autoload.php';

    $service = new Service();
    return $service->run();
})();
