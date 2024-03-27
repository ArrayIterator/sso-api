<?php
declare(strict_types=1);

namespace Pentagonal\Sso;

use Pentagonal\Sso\Core\Service;
use function define;
use const DIRECTORY_SEPARATOR;

return (function () {

    require __DIR__ .'/vendor/autoload.php';

    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__);

    define('APP_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'app');

    return new Service();
})();
