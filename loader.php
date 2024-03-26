<?php
declare(strict_types=1);

namespace Pentagonal\Sso;

use Pentagonal\Sso\Core\Service;
use function define;

return (function () {

    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__);
    define('APP_PATH', \ROOT_PATH . '/app');
    require __DIR__ .'/vendor/autoload.php';

    return new Service();
})();
