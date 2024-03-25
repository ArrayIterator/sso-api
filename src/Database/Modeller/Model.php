<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Modeller;

use Pentagonal\Sso\Core\Database\Builder\QueryBuilder;
use Pentagonal\Sso\Core\Database\Connection;
use Pentagonal\Sso\Core\Database\Exceptions\RuntimeException;
use Pentagonal\Sso\Core\Database\Schema\Table;
use Pentagonal\Sso\Core\Database\Types\Integer;
use Stringable;
use function array_change_key_case;
use function array_key_exists;
use function array_map;
use function array_values;
use function func_num_args;
use function is_a;
use function is_array;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function reset;
use function sprintf;
use function str_ends_with;
use function str_replace;
use function strtolower;
use function trim;

abstract class Model extends Result
{
    /**
     * @var Connection Database Connection
     */
    protected Connection $connection;

    /**
     * @var string Table Name
     */
    protected string $table;

    /**
     * @var bool Use Prefix
     */
    protected bool $usePrefix = true;

    /**
     * @var string|array|null Primary Key
     */
    protected string|array|null $primaryKey = null;

    /**
     * @var ?string Result Class
     */
    protected ?string $resultClass = null;

    /**
     * @var Connection|null Global Connection
     */
    public static ?Connection $globalConnection = null;

    /**
     * @var QueryBuilder|null Query Builder
     */
    protected ?QueryBuilder $queryBuilder = null;

    final public function __construct(Connection|Model $connection)
    {
        if ($connection instanceof Model) {
            $connection = $connection->getConnection();
        }
        $this->connection = $connection;
        self::$globalConnection ??= $connection;
        $this->guessTable($connection);
        $this->queryBuilder = new QueryBuilder($connection);
        $this->queryBuilder->table($this->getTable());
        $this->onConstruct();
        parent::__construct($this);
    }

    /**
     * @return class-string<Result>
     */
    public function getResultClass() : string
    {
        if (!$this->resultClass || !is_a($this->resultClass, Result::class, true)) {
            $this->resultClass = $this::class;
        }
        return $this->resultClass;
    }

    /**
     * @override
     */
    protected function onConstruct()
    {
    }

    /**
     * @return string
     */
    public function getTable() : string
    {
        return $this->table;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return bool
     */
    public function isUsePrefix(): bool
    {
        return $this->usePrefix;
    }

    /**
     * @return string|array|null
     */
    public function getPrimaryKey(): array|string|null
    {
        return $this->primaryKey;
    }

    public static function getGlobalConnection(): ?Connection
    {
        return self::$globalConnection;
    }

    public function getObjectTable(): ?Table
    {
        return $this->connection->getSchema()->getTables()->get($this->table);
    }

    private function guessTable(Connection $connection) : void
    {
        $prefix = $this->usePrefix
            ? $connection->getConfiguration()->getPrefix()
            : '';
        $table = $this->table?? '';
        if ($table === '') {
            $table = get_class($this);
            $table = substr($table, strrpos($table, '\\') + 1);
            $table = strtolower($table);
        }

        $objectTable = null;
        $schema = $connection->getSchema();
        if ($prefix) {
            $table = $prefix . $table;
        }

        $theTable = $table;
        $objectTable ??= $schema->getTables()->get($table);
        /*
         * START GUESS
         */
        if (!$objectTable && str_ends_with($table, 's')) {
            $table = substr($table, 0, -1);
            $objectTable = $schema->getTables()->get($table);
        }
        if (!$objectTable && str_ends_with($table, 'ies')) {
            $table = substr($table, 0, -3) . 'y';
            $objectTable = $schema->getTables()->get($table);
        }
        if (!$objectTable && str_ends_with($table, 'es')) {
            $table = substr($table, 0, -2);
            $objectTable = $schema->getTables()->get($table);
        }
        if (!$objectTable && !str_ends_with($table, 'y')) {
            if (!str_ends_with($table, 's')) {
                $table .= 's';
                $objectTable = $schema->getTables()->get($table);
            }
            if (!$objectTable && !str_ends_with($table, 'es')) {
                $table .= 'es';
                $objectTable = $schema->getTables()->get($table);
            }
        } elseif (!$objectTable && str_ends_with($table, 'y')) {
            if (!str_ends_with($table, 'ies')) {
                $table .= 'ies';
                $objectTable = $schema->getTables()->get($table);
            }
        }
        // end

        if (!$objectTable) {
            throw new RuntimeException(
                sprintf('Table %s Not Found', $theTable)
            );
        }
        $this->table = $objectTable->getName();
        if (!empty($this->primaryKey)) {
            if (count($this->primaryKey) === 1) {
                $this->primaryKey = reset($this->primaryKey);
            }
            return;
        }
            $columns = $objectTable->getColumns();
        foreach ($columns as $column) {
            if ($column->isAutoIncrement()) {
                $this->primaryKey = $column->getName();
                break;
            }
        }

            $unique = [];
            /**
             * @var \Pentagonal\Sso\Core\Database\Schema\Index $index
             * @noinspection PhpFullyQualifiedNameUsageInspection
             */
        foreach ($objectTable->getIndexes() as $index) {
            if (!$index->isPrimary()) {
                if (!$index->isUnique()
                    && count($index->getColumns()) !== 1
                ) {
                    continue;
                }
                $unique[] = $index;
                continue;
            }

            $this->primaryKey = [];
            foreach ($index->getColumns() as $column) {
                $this->primaryKey[] = $column['name'];
            }
            if (count($this->primaryKey) === 1) {
                $this->primaryKey = reset($this->primaryKey);
            }
            return;
        }
        if (empty($unique)) {
            return;
        }
        foreach ($unique as $index) {
            $column = $index->getColumns();
            $column = $columns->get(reset($column)['name']);
            if ($column->getType() instanceof Integer) {
                $this->primaryKey = $column->getName();
                return;
            }
        }

        /**
         * @var \Pentagonal\Sso\Core\Database\Schema\Index $index
         * @noinspection PhpFullyQualifiedNameUsageInspection
         */
        $index = reset($unique);
        $column = $index->getColumns();
        $this->primaryKey = reset($column);
    }

    public static function setCurrentConnection(Connection $connection): void
    {
        self::$globalConnection = $connection;
    }

    /**
     * @param string $operator
     * @return string|null
     */
    final public static function determineOperatorMethod(string $operator) : ?string
    {
        $operator = trim($operator)?:'=';
        $newOperator = str_replace(' ', '_', strtolower($operator));
        return match ($newOperator) {
            'eq', 'equal', 'equals', 'is', '=' => 'eq',
            'neq', 'not_equal', 'not_equals', 'is_not', '!=' => 'neq',
            'gt', 'greater than', '>' => 'gt',
            'gte', 'greater than or equal', '>=' => 'gte',
            'lt', 'less_than', '<' => '<',
            'lte', 'less_than_or_equal', '<=' => 'lte',
            'like', '%' => 'like',
            'not_like', '!%' => 'notLike',
            'in', '()' => 'in',
            'not_in', '!in', '!()' => 'notIn',
            'between', '<=>' => 'between',
            'not_between', '<!>' => 'notBetween',
            'isNull', 'is_null', 'null' => 'isNull',
            'isNotNull', 'is_not_null', 'not_null', '!null' => 'isNotNull',
            default => null,
        };
    }

    protected ?Connection\Statement $statement = null;

    /**
     * @var Result|false|null
     */
    private Result|false|null $current = null;

    /**
     * @var Result|null
     */
    private Result|null $previous = null;

    private int $incrementResult = 0;

    /**
     * Implement get() to get data or current fetch
     *
     * @param ?string $key
     * @return false|Result|null|mixed
     */
    public function get(?string $key = null) : mixed
    {
        if ($key === null) {
            if ($this->current !== null) {
                return $this->current;
            }
            return $this->fetch();
        }
        return parent::get($key);
    }

    /**
     * @return QueryBuilder|null
     */
    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function fetch()
    {
        if ($this->current === false) {
            return false;
        }

        if (!$this->statement) {
            $this->statement = $this->queryBuilder->select('*')->execute();
        }

        $current = $this->statement->fetchObject(
            $this->getResultClass(),
            [$this]
        );
        if ($this->current instanceof Result) {
            $this->previous = $this->current;
        }
        $this->incrementResult++;
        $this->current = $current instanceof Result ? $current : false;
        if ($this->current === false) {
            $this->incrementResult--;
            $this->statement->closeCursor();
        }
        return $this->current;
    }

    public function first()
    {
        if ($this->current === null) {
            return $this->fetch();
        }
        if ($this->incrementResult > 1) {
            return $this->current;
        }
        if ($this->current !== false) {
            $this->incrementResult = 0;
            $this->statement?->closeCursor();
            $this->statement = null;
            $this->current = null;
        }
        return $this->fetch()?:null;
    }

    public function last(): ?Result
    {
        if ($this->current === false) {
            return $this->previous?:null;
        }
        do {
            $this->previous = $this->current;
        } while ($this->fetch() !== false);

        return $this->previous?:null;
    }

    public function next()
    {
        return $this->fetch();
    }

    public function prev() : ?Result
    {
        return $this->previous?:null;
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param Connection|null $connection
     * @param bool $setAsGlobal
     * @return static
     */
    public static function where(
        string $column,
        string $operator,
        mixed $value,
        ?Connection $connection = null,
        bool $setAsGlobal = false
    ): static {
        $connection ??= self::$globalConnection;
        if (!$connection) {
            throw new RuntimeException(
                'Connection Not Found'
            );
        }

        if ($setAsGlobal) {
            self::setCurrentConnection($connection);
        }

        return (new static($connection))->and($column, $operator, $value);
    }

    /**
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return static
     */
    public function and(
        string $column,
        string $operator,
        mixed $value,
    ) : static {
        $operator = trim($operator)?:'=';
        $method = self::determineOperatorMethod($operator);
        if ($method === null) {
            throw new RuntimeException(
                sprintf(
                    'Operator "%s" is invalid',
                    $operator
                )
            );
        }

        $qb = $this->queryBuilder;
        $ex = $qb->expr();
        $value = is_array($value) ? array_values($value) : [$value];
        if ($method === 'in' || $method === 'notIn') {
            if (!is_iterable($value)) {
                $in = [$value];
            } else {
                $in = iterator_to_array($value);
            }
            if (!empty($in)) {
                $inArray = [];
                foreach ($value as $key => $val) {
                    $inArray[$key] = $qb->createNamedParameter($val);
                }
                $qb->andWhere($ex->in($column, $inArray));
            }
            return $this;
        }
        foreach ($value as $val) {
            if (!is_iterable($val) && $method !== 'in' && $method !== 'notIn') {
                if ($method === 'isNull'
                    || $method === 'isNotNull'
                ) {
                    $qb->andWhere(
                        $ex->{$method}($column)
                    );
                    continue;
                }
                $qb->andWhere(
                    $ex->{$method}($column, $qb->createNamedParameter($val))
                );
                continue;
            }

            if (!is_iterable($val)) {
                $in = [$val];
            } else {
                $in = iterator_to_array($val);
            }
            if (!empty($in)) {
                $inArray = [];
                foreach ($in as $key => $value) {
                    $inArray[$key] = $qb->createNamedParameter($value);
                }
                $qb->andWhere($ex->in($column, $inArray));
            }
        }

        return $this;
    }

    /**
     * @return LazyResult Lazy Result
     */
    public function results() : LazyResult
    {
        return new LazyResult($this);
    }

    /**
     * @param string|int|float|Stringable|array|null $whereCause
     * @return static
     */
    public static function find(
        string|int|float|Stringable|array $whereCause = null,
    ) : static {
        $model = new static(self::$globalConnection);
        $primaryKey = $model->getPrimaryKey();
        if (empty($primaryKey)) {
            throw new RuntimeException(
                sprintf('Primary Key Not Found in Table "%s"', $model->getTable())
            );
        }

        if (func_num_args() > 0) {
            $primaryKey = is_string($primaryKey) ? [$primaryKey] : $primaryKey;
            $ids = $whereCause;
            if (!is_array($ids)) {
                $ids = [reset($primaryKey) => $ids];
            } elseif (count($primaryKey) === 1) {
                $containString = false;
                foreach ($ids as $key => $value) {
                    if (is_string($key)) {
                        $containString = true;
                        break;
                    }
                }
                if (!$containString) {
                    $ids = [reset($primaryKey) => $ids];
                }
            }

            $ids = array_change_key_case($ids, CASE_LOWER);
            $primaryKey = array_map('strtolower', $primaryKey);
            foreach ($primaryKey as $value) {
                if (!array_key_exists($value, $ids)) {
                    continue;
                }
                $key = $value;
                $value = $ids[$key];
                if ($value === null) {
                    $model->and($key, 'null', null);
                } elseif (is_array($value)) {
                    if (empty($value)) {
                        $model->and($key, '=', '');
                    } else {
                        $model->and($key, 'in', $value);
                    }
                } else {
                    $value = (string)$value;
                    $model->and($key, '=', $value);
                }
            }
        }

        return $model->limit(1);
    }

    public function limit(?int $limit) : static
    {
        $this->queryBuilder->limit($limit);
        return $this;
    }

    public function offset(?int $offset) : static
    {
        $this->queryBuilder->offset($offset);
        return $this;
    }

    /**
     * Reset current operation
     *
     * @return void
     */
    public function reset() : void
    {
        $this->statement = null;
        $this->current = null;
        $this->previous = null;
        $this->incrementResult = 0;
        $this->queryBuilder = $this->queryBuilder->select('*');
    }

    public function __clone(): void
    {
        $this->reset();
        $this->queryBuilder = clone ($this->queryBuilder);
    }
}
