<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Connection;

use PDO;
use PDOStatement;

class Statement extends PDOStatement
{
    public function __destruct()
    {
        $this->closeCursor();
    }

    /**
     * Fetch All Assoc
     *
     * @return array|false
     */
    public function fetchAllAssoc() : array|false
    {
        return $this->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch Assoc
     *
     * @return array|false
     */
    public function fetchAssoc() : array|false
    {
        return $this->fetch(PDO::FETCH_ASSOC);
    }
}
