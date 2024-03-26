<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Public;

// doing
use function define;

return (function () {

    // phpcs:disable PSR1.Files.SideEffects
    define('PUBLIC_PATH', __DIR__);

    $loader = require \ROOT_PATH .'/loader.php';
    return $loader->run();
})();
