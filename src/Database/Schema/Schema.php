<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Pentagonal\Sso\Core\Database\Connection;

class Schema
{
    /**
     * @var Connection The connection.
     */
    protected Connection $connection;

    /**
     * @var Tables The tables.
     */
    protected Tables $tables;

    /**
     * Schema constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Gets the tables.
     *
     * @return Tables
     */
    public function getTables(): Tables
    {
        if (!isset($this->tables)) {
            $this->tables = new Tables($this);
        }
        return $this->tables;
    }

    public function __clone(): void
    {
        $this->tables = clone $this->getTables();
    }
}
