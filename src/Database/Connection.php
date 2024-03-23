<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database;

use DateTimeZone;
use PDO;
use PDOException;
use Pentagonal\Sso\Core\Database\Builder\Expression;
use Pentagonal\Sso\Core\Database\Builder\QueryBuilder;
use Pentagonal\Sso\Core\Database\Connection\PDOWrapper;
use Pentagonal\Sso\Core\Database\Connection\Statement;
use Pentagonal\Sso\Core\Database\Exceptions\PDODatabaseException;
use Pentagonal\Sso\Core\Database\Exceptions\RuntimeException;
use Pentagonal\Sso\Core\Database\Schema\DatabaseSchemaHelper;
use Pentagonal\Sso\Core\Database\Schema\Schema;
use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use Throwable;
use function array_shift;
use function array_values;
use function count;
use function end;
use function explode;
use function get_object_vars;
use function implode;
use function is_array;
use function key;
use function microtime;
use function preg_match;
use function reset;
use function str_contains;
use function trim;
use function vsprintf;

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
     * @var ?PDOWrapper
     */
    private ?PDOWrapper $pdo = null;

    /**
     * @var Configuration
     */
    private Configuration $configuration;

    /**
     * @var ?EventManagerInterface
     */
    protected ?EventManagerInterface $eventManager = null;

    /**
     * @var ?string $databaseName database name
     */
    protected ?string $databaseName = null;

    /**
     * @var ?string $dsn
     */
    private ?string $dsn = null;

    /**
     * @var Schema
     */
    private Schema $schema;

    /**
     * @var ?DateTimeZone
     */
    private ?DateTimeZone $sqlTimeZone = null;

    /**
     * Connection constructor.
     *
     * @param Configuration $configuration
     * @param ?EventManagerInterface $eventManager
     */
    public function __construct(
        Configuration $configuration,
        ?EventManagerInterface $eventManager = null
    ) {
        $this->setEventManager($eventManager);
        $this->configuration = $configuration->getLockedObject();
        $this->setLogQuery($this->configuration->isLogQuery());
        $this->setMaxLog($this->configuration->getMaxLog());
        try {
            $this->sqlTimeZone = new DateTimeZone($this->configuration->getTimezone());
        } catch (Throwable) {
            // pass
        }
    }

    /**
     * @param ?EventManagerInterface $eventManager
     */
    public function setEventManager(?EventManagerInterface $eventManager) : void
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @return ?EventManagerInterface
     */
    public function getEventManager() : ?EventManagerInterface
    {
        return $this->eventManager;
    }
    /**
     * @return Expression the expression builder
     */
    public function getExpressionBuilder(): Expression
    {
        return new Expression();
    }

    /**
     * @return QueryBuilder the query builder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * @return Schema
     */
    public function getSchema() : Schema
    {
        return $this->schema ??= DatabaseSchemaHelper::getSchema($this);
    }

    /**
     * Create a new schema
     *
     * @return Schema
     */
    public function createNewSchema() : Schema
    {
        return new Schema($this);
    }

    /**
     * Quote a table
     *
     * @param string|array $table
     * @return string|array
     */
    public function columnQuote(string|array $table): string|array
    {
        if (is_array($table)) {
            foreach ($table as $key => $value) {
                $table[$key] = $this->columnQuote($value);
            }
            return $table;
        }
        $trimmedTable = trim($table);
        if (preg_match('~^(`.+`|[0-9]+|\*)?$~', $trimmedTable)) {
            return $table;
        }
        if (str_contains($trimmedTable, '.')) {
            return implode(
                '.',
                $this->columnQuote(explode('.', $table))
            );
        }

        return "`$trimmedTable`";
    }

    /**
     * @return bool true if the connection is alive
     */
    public function ping() : bool
    {
        return $this->exec('SELECT 1') !== false;
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

    public function getSQLTimeZone(): DateTimeZone
    {
        return $this->sqlTimeZone;
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
        $this->getEventManager()?->trigger($event, ...$arguments);
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
        if ($this->pdo) {
            return $this->pdo;
        }
        if (!$this->configuration->getDatabase()) {
            throw new RuntimeException('Database name is not defined');
        }

        $this->trigger('database.connect.start', $this->configuration);
        $modes = [
            'NO_ENGINE_SUBSTITUTION',
            'NO_ZERO_DATE',
            'NO_ZERO_IN_DATE',
            'ERROR_FOR_DIVISION_BY_ZERO'
        ];
        // add sql mode if strict is enabled
        if ($this->configuration->isStrict()) {
            $modes[] = 'STRICT_TRANS_TABLES';
        }
        $commands = vsprintf(
            'SET NAMES \'%s\' COLLATE \'%s\', time_zone = \'%s\', sql_mode = \'%s\';',
            [
                $this->configuration->getCharset(),
                $this->configuration->getCollation(),
                $this->configuration->getTimezone(),
                implode(',', $modes)
            ]
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => $this->configuration->isPersistent(),
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => $commands,
        ];
        $this->getEventManager()?->trigger(
            'database.connect.start',
            $this->configuration,
            $options
        );

        $this->pdo = new PDOWrapper(
            $this->getDSN(),
            $this->configuration->getUsername(),
            $this->configuration->getPassword(),
            $options
        );
        $this->trigger(
            'database.connect.end',
            $this
        );
        // GET SQL TIMEZONE
        $stmt = $this->pdo->query(
            'SELECT @@session.time_zone AS `timezone`'
        );
        $timezone = $stmt->fetchAssoc()['timezone'];
        $stmt->closeCursor();
        if ($timezone) {
            try {
                $this->sqlTimeZone = new DateTimeZone($timezone);
            } catch (Throwable) {
                // pass
            }
        }
        return $this->pdo;
    }

    public function getDSN() : string
    {
        return $this->dsn ??= $this->configuration->getDsn();
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @return string The database name
     */
    public function getDatabaseName() : string
    {
        if ($this->databaseName) {
            return $this->databaseName;
        }
        return $this->databaseName = $this->first('SELECT DATABASE() AS `database`')['database'];
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
            $result = $this->getPDO()->exec($query);
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
     * @return Statement
     */
    public function prepare(string $query) : Statement
    {
        $this->trigger('database.prepare.start', $query);
        // log
        $this->log('prepare', ['query' => $query]);
        try {
            $stmt = $this->getPDO()->prepare($query);
            return $stmt;
        } finally {
            $this->logEnd(!empty($stmt));
            $this->trigger('database.prepare.end', $query);
        }
    }

    /**
     * @param string $query
     * @param array $params
     * @param-out Statement $stmt
     * @return Statement|false
     */
    public function query(string $query, array $params = []) : false|Statement
    {
        $this->trigger('database.query.start', $query, $params);
        try {
            $this->log('query', ['query' => $query, 'params' => $params]);
            $stmt = $this->getPDO()->prepare($query);
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
     * Unbuffered query
     *
     * @param string $query
     * @param array $params
     * @param callable $callback
     * @return mixed
     */
    public function unbufferedQuery(
        string $query,
        array $params,
        callable $callback
    ): mixed {
        $pdo = $this->getPDO();
        $currentStatus = $pdo->getAttribute(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
        );
        $pdo->setAttribute(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
            false
        );
        $this->trigger('database.unbufferedQuery.start', $query, $params);
        try {
            $stmt = $this->query($query, $params);
            if ($stmt instanceof Statement) {
                return $callback($stmt, $this);
            }
            return false;
        } finally {
            $this->trigger('database.unbufferedQuery.end', $query, $params);
            try {
                // fallback
                $pdo->setAttribute(
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                    $currentStatus
                );
            } catch (Throwable) {
                // pas
            }
        }
    }

    /**
     * Buffered query
     *
     * @param string $query
     * @param array $params
     * @param callable $callback
     * @return mixed
     */
    public function bufferedQuery(
        string $query,
        array $params,
        callable $callback
    ) : mixed {
        $pdo = $this->getPDO();
        $currentStatus = $pdo->getAttribute(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
        );
        $pdo->setAttribute(
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
            true
        );
        $this->trigger('database.bufferedQuery.start', $query, $params);
        try {
            $stmt = $this->query($query, $params);
            if ($stmt instanceof Statement) {
                return $callback($stmt, $this);
            }
            return false;
        } finally {
            $this->trigger('database.bufferedQuery.end', $query, $params);
            try {
                // fallback
                $pdo->setAttribute(
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                    $currentStatus
                );
            } catch (Throwable) {
                // pas
            }
        }
    }

    /**
     * Fetch all rows of the result
     *
     * @param string $query
     * @param array $params
     * @param-out Statement $stmt
     * @return array|false The result, or false if fail
     */
    public function all(string $query, array $params = []) : array|false
    {
        $this->trigger('database.fetchAll.start', $query, $params);
        try {
            $this->log('fetchAll', ['query' => $query, 'params' => $params]);
            $result = false;
            if (!($stmt = $this->getPDO()->prepare($query))) {
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
            $this->trigger('database.fetchAll.end', $query, $params, $result??false);
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
            if (!($stmt = $this->getPDO()->prepare($query))) {
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
            if (!($stmt = $this->getPDO()->prepare($query))) {
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
     * @return array|null|false The result, or null if failed, false if no result
     */
    public function position(string $query, array $params = [], int $position = 0) : array|null|false
    {
        $this->trigger('database.position.start', $query, $params, $position);
        try {
            if ($position < 0) {
                return false;
            }
            $this->log('position', ['query' => $query, 'params' => $params, 'position' => $position]);
            if (!($stmt = $this->getPDO()->prepare($query))) {
                return false;
            }
            $status = $stmt->execute($params);
            if (!$status) {
                $stmt->closeCursor();
                return false;
            }
            $i = 0;
            while (($result = $stmt->fetch(PDO::FETCH_ASSOC))) {
                if ($i++ === $position) {
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
        return $this->pdo?->lastInsertId()??false;
    }

    /**
     * Magic method to prevent serialization
     *
     * @return array
     */
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
            return $this->getPDO()->{$name}(...$arguments);
        } catch (PDOException $e) {
            throw new PDODatabaseException($e);
        }
    }

    public function __debugInfo(): array
    {
        $var = get_object_vars($this);
        $var['dsn'] = '<redacted>';
        return $var;
    }
}
