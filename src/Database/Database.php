<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database;

use PDO;
use Pentagonal\Sso\Core\Database\Builder\Expression;
use Pentagonal\Sso\Core\Database\Builder\QueryBuilder;
use Pentagonal\Sso\Core\Database\Exceptions\RuntimeException;
use Pentagonal\Sso\Core\Services\Interfaces\EventManagerInterface;
use function explode;
use function implode;
use function is_array;
use function preg_match;
use function str_contains;
use function vsprintf;

/**
 * @mixin Connection
 */
class Database
{
    private ?Connection $connection = null;

    private Configuration $configuration;

    /**
     * @var EventManagerInterface|null
     */
    private ?EventManagerInterface $eventManager;

    /**
     * Database constructor.
     *
     * @param Configuration $configuration
     * @param ?EventManagerInterface $eventManager
     */
    public function __construct(
        Configuration $configuration,
        ?EventManagerInterface $eventManager = null
    ) {
        $this->configuration = $configuration->getLockedObject();
        $this->setEventManager($eventManager);
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
     * Get the configuration
     *
     * @return Configuration
     */
    public function getConfiguration() : Configuration
    {
        return $this->configuration;
    }

    /**
     * Get the connection
     *
     * @return Connection
     */
    public function getConnection() : Connection
    {
        if ($this->connection !== null) {
            return $this->connection;
        }
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
        $this->connection = new Connection(
            new PDOWrapper(
                $this->configuration->getDsn(),
                $this->configuration->getUsername(),
                $this->configuration->getPassword(),
                $options
            ),
            $this,
            $this->configuration->isLogQuery()
        );
        $this->connection->setMaxLog($this->configuration->getMaxLog());
        $this->getEventManager()?->trigger(
            'database.connect.end',
            $this->connection
        );
        return $this->connection;
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
     * Magic method destructor
     */
    public function __destruct()
    {
        $this->connection = null;
    }

    public function __sleep(): array
    {
        throw new RuntimeException('Cannot serialize the connection');
    }

    /**
     * Magic method to call the PDO methods
     */
    public function __call(string $name, array $arguments)
    {
        return $this->getConnection()->$name(...$arguments);
    }
}
