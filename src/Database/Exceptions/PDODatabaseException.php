<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Exceptions;

use PDOException;
use Pentagonal\Sso\Core\Database\Exceptions\Interfaces\DatabaseExceptionInterface;

class PDODatabaseException extends RuntimeException implements DatabaseExceptionInterface
{
    protected PDOException $pdoException;

    public function __construct(PDOException $exception)
    {
        $this->pdoException = $exception;
        parent::__construct($exception->getMessage(), 0, $exception->getPrevious());
    }

    public function getPdoException(): PDOException
    {
        return $this->pdoException;
    }
}
