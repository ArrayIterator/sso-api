<?php
declare(strict_types=1);

namespace Pentagonal\Sso;

use Pentagonal\Sso\Core\Service;
use function define;
use const DIRECTORY_SEPARATOR;

return (function () {

    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__);

    if (! defined('APP_PATH')) {
        define('APP_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'app');
    }

    require __DIR__ .'/vendor/autoload.php';

    return new Service();
})();
