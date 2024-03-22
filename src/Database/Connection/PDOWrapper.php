<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Connection;

use PDO;
use PDOException;
use PDOStatement;
use Pentagonal\Sso\Core\Database\Exceptions\PDODatabaseException;

/**
 * @method Statement query(string $statement)
 * @method Statement prepare(string $statement, array $driver_options = [])
 * @method mixed quote(string $string, int $parameter_type = PDO::PARAM_STR)
 */
class PDOWrapper extends PDO
{
    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null)
    {
        $options ??= [];
        $options[PDO::ATTR_STATEMENT_CLASS] = [Statement::class];
        try {
            parent::__construct($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new PDODatabaseException($e);
        }
    }

    /**
     * @inheritDoc
     *
     * @param string $statement
     * @return int|false
     */
    final public function exec(string $statement): int|false
    {
        try {
            $stmt = $this->query($statement);
        } catch (PDOException $e) {
            throw new PDODatabaseException($e);
        }
        if ($stmt instanceof PDOStatement) {
            return $stmt->rowCount();
        }
        return false;
    }

    /**
     * @param int $attribute
     * @param mixed $value
     * @return bool
     */
    final public function setAttribute(int $attribute, mixed $value): bool
    {
        // ignore the statement class
        if ($attribute === PDO::ATTR_STATEMENT_CLASS) {
            $value = [Statement::class];
        }
        try {
            return parent::setAttribute($attribute, $value);
        } catch (PDOException $e) {
            throw new PDODatabaseException($e);
        }
    }
}
