<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database;

use PDOStatement;

class Statement extends PDOStatement
{
    public function __destruct()
    {
        $this->closeCursor();
    }
}
