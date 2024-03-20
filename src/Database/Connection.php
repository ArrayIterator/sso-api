<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database;

use PDO;
use PDOException;
use PDOStatement;
use Pentagonal\Sso\Core\Database\Exceptions\PDODatabaseException;
use Pentagonal\Sso\Core\Database\Exceptions\RuntimeException;
use function count;
use function end;
use function key;

/**
 * @mixin PDOWrapper
 */
class Connection
{
    /**
     * @var bool Log query
     */
    protected bool $logQuery = false;

    /**
     * @var int Max log
     */
    protected int $maxLog = 100;

    /**
     * @var array Log
     */
    protected array $logs = [];

    /**
     * @param PDOWrapper $pdo
     * @param Database $database
     * @param bool $logQuery
     */
    public function __construct(
        private readonly PDOWrapper $pdo,
        private readonly Database $database,
        bool $logQuery = false
    ) {
        $this->setLogQuery($logQuery);
    }

    /**
     * Get log query
     *
     * @return bool
     */
    public function isLogQuery(): bool
    {
        return $this->logQuery;
    }

    /**
     * Set log query
     *
     * @param bool $logQuery
     */
    public function setLogQuery(bool $logQuery): void
    {
        $this->logQuery = $logQuery;
    }

    /**
     * Set max log
     *
     * @param int $maxLog
     */
    public function setMaxLog(int $maxLog): void
    {
        $this->maxLog = $maxLog;
    }

    /**
     * Get max log
     *
     * @return int
     */
    public function getMaxLog(): int
    {
        return $this->maxLog;
    }

    /**
     * Trigger event
     *
     * @param string $event
     * @param ...$arguments
     * @return void
     */
    private function trigger(string $event, ...$arguments) : void
    {
        $this->database->getEventManager()?->trigger($event, ...$arguments);
    }

    /**
     * Log
     *
     * @param string $event
     * @param array $arguments
     */
    private function log(string $event, array $arguments = []) : void
    {
        if ($this->isLogQuery()) {
            if (count($this->logs) >= $this->maxLog) {
                $removedLog = array_shift($this->logs);
                $this->trigger('database.log.removed', $removedLog);
            }
            $this->logs[] = [
                'event' => $event,
                'arguments' => $arguments,
                'start' => microtime(true),
                'end' => null,
                'status' => null,
            ];
        }
    }

    private function logEnd(bool $status) : void
    {
        if (!$this->isLogQuery()) {
            return;
        }
        $logs = end($this->logs);
        $key = key($this->logs);
        if ($key === false) {
            return;
        }
        $logs['end'] = microtime(true);
        $logs['status'] = $status;
        $this->logs[$key] = $logs;
    }

    /**
     * Get PDO
     *
     * @return PDOWrapper
     */
    public function getPDO() : PDOWrapper
    {
        return $this->pdo;
    }

    /**
     * Execute a query
     *
     * @param string $query
     * @return int|false
     */
    public function exec(string $query) : int|false
    {
        $this->trigger('database.exec.start', $query);
        // log
        $this->log('exec', ['query' => $query]);
        try {
            $result = $this->pdo->exec($query);
            return $result;
        } catch (PDOException $e) {
            throw new PDODatabaseException($e);
        } finally {
            $this->logEnd(($result??false) !== false);
            $this->trigger(
                'database.exec.end',
                $query,
                $result??false
            );
        }
    }

    /**
     * Prepare a statement
     *
     * @param string $query
     * @return PDOStatement
     */
    public function prepare(string $query) : PDOStatement
    {
        $this->trigger('database.prepare.start', $query);
        // log
        $this->log('prepare', ['query' => $query]);
        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt;
        } finally {
            $this->logEnd(!empty($stmt));
            $this->trigger('database.prepare.end', $query);
        }
    }

    /**
     * @param string $query
     * @param array $params
     * @param-out PDOStatement $stmt
     * @return PDOStatement|false
     */
    public function query(string $query, array $params = []) : false|PDOStatement
    {
        $this->trigger('database.query.start', $query, $params);
        try {
            $this->log('query', ['query' => $query, 'params' => $params]);
            $stmt = $this->pdo->prepare($query);
            if ($stmt === false) {
                return false;
            }
            $status = $stmt->execute($params);
            if (!$status) {
                $stmt->closeCursor();
                return false;
            }
            return $stmt;
        } catch (PDOException $e) {
            throw new PDODatabaseException($e);
        } finally {
            $this->logEnd($status??false);
            $this->trigger('database.query.end', $query, $params);
        }
    }

    /**
     * Fetch all rows of the result
     *
     * @param string $query
     * @param array $params
     * @param-out PDOStatement $stmt
     * @return array|false The result, or false if fail
     */
    public function all(string $query, array $params = []) : array|false
    {
        $this->trigger('database.fetchAll.start', $query, $params);
        try {
            $this->log('fetchAll', ['query' => $query, 'params' => $params]);
            $result = false;
            if (!($stmt = $this->pdo->prepare($query))) {
                return false;
            }
            $status = $stmt->execute($params);
            if (!$status) {
                $stmt->closeCursor();
                return false;
            }
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } finally {
            $this->logEnd($status??false);
            $this->trigger('database.fetchAll.end', $query, $params, $result);
        }
    }

    /**
     * Get the first row of the result
     *
     * @param string $query
     * @param array $params
     * @return array|null|false The result, or null if no result, false if fail
     */
    public function first(string $query, array $params = []) : array|null|false
    {
        $this->trigger('database.first.start', $query, $params);
        try {
            $this->log('first', ['query' => $query, 'params' => $params]);
            $result = null;
            if (!($stmt = $this->pdo->prepare($query))) {
                return false;
            }
            $status = $stmt->execute($params);
            if (!$status) {
                $stmt->closeCursor();
                return false;
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result;
        } finally {
            $this->logEnd(($result??null) !== false);
            $this->trigger('database.first.end', $query, $params, $result??null);
        }
    }

    /**
     * Get the last row of the result
     *
     * @param string $query
     * @param array $params
     * @return ?array The last row of the result, or null if no result
     */
    public function last(string $query, array $params = []) : array|false
    {
        $this->trigger('database.last.start', $query, $params);
        try {
            $this->log('last', ['query' => $query, 'params' => $params]);
            $result = null;
            if (!($stmt = $this->pdo->prepare($query))) {
                return false;
            }
            $status = $stmt->execute($params);
            if (!$status) {
                $stmt->closeCursor();
                return false;
            }
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result = $row;
            }
            $stmt->closeCursor();
            return $result;
        } finally {
            $this->logEnd(($result??false) !== false && ($result??null) !== null);
            $this->trigger(
                'database.last.end',
                $query,
                $params,
                $result??null
            );
        }
    }

    /**
     * Search the result by offset position
     *
     * @param string $query
     * @param array $params
     * @param int $position
     * @return array|null|false The result, or null if fail, false if no result
     */
    public function position(string $query, array $params = [], int $position = 0) : array|null|false
    {
        $this->trigger('database.position.start', $query, $params, $position);
        try {
            $this->log('position', ['query' => $query, 'params' => $params, 'position' => $position]);
            if (!($stmt = $this->pdo->prepare($query))) {
                return false;
            }
            $status = $stmt->execute($params);
            if (!$status) {
                $stmt->closeCursor();
                return false;
            }
            $result = false;
            for ($i = 0; $i < $position; $i++) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result === false) {
                    break;
                }
            }
            $stmt->closeCursor();
            return $result;
        } finally {
            $this->logEnd(($result??false) !== false && ($result??null) !== null);
            $this->trigger(
                'database.position.end',
                $query,
                $params,
                $position,
                $result??null
            );
        }
    }


    /**
     * @return array<array{
     *     event: string,
     *     arguments: array<string, mixed>
     * }>
     */
    public function getLogs() : array
    {
        reset($this->logs);
        if ($this->logs !== [] && key($this->logs) !== 0) {
            $this->logs = array_values($this->logs);
        }

        return $this->logs;
    }

    /**
     * Clear logs
     */
    public function clearLogs() : void
    {
        $this->logs = [];
    }

    /**
     * @return string|false
     */
    public function lastInsertId() : string|false
    {
        return $this->pdo->lastInsertId();
    }

    public function __sleep(): array
    {
        throw new RuntimeException('Cannot serialize the connection');
    }

    /**
     * Magic method
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        try {
            return $this->pdo->{$name}(...$arguments);
        } catch (PDOException $e) {
            throw new PDODatabaseException($e);
        }
    }
}
