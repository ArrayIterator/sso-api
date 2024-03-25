<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Modeller;

use Pentagonal\Sso\Core\Database\Builder\QueryBuilder;
use Pentagonal\Sso\Core\Database\Connection;
use Pentagonal\Sso\Core\Database\Exceptions\RuntimeException;
use Pentagonal\Sso\Core\Database\Schema\Column;
use Pentagonal\Sso\Core\Database\Schema\Table;
use Pentagonal\Sso\Core\Database\Types\Integer;
use Stringable;
use function array_change_key_case;
use function array_key_exists;
use function array_map;
use function array_values;
use function func_num_args;
use function get_class;
use function implode;
use function in_array;
use function is_a;
use function is_array;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function key;
use function reset;
use function sprintf;
use function str_ends_with;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr;
use function trim;
use const CASE_LOWER;

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
     * @var array Columns
     */
    protected array $columns = [];

    /**
     * @var array Disallowed Change
     */
    protected array $disallowedChange = [];

    /**
     * @var bool Use Prefix
     */
    protected bool $usePrefix = true;

    /**
     * primary key
     */
    protected ?array $primaryKey = null;

    /**
     * @var Connection|null Global Connection
     */
    public static ?Connection $globalConnection = null;

    /**
     * @var QueryBuilder|null Query Builder
     */
    protected ?QueryBuilder $queryBuilder = null;

    protected ?Connection\Statement $statement = null;

    /**
     * @var static|false|null
     */
    private Model|false|null $current = null;

    /**
     * @var static|null
     */
    private Model|null $previous = null;

    /**
     * @var int
     */
    private int $incrementResult = 0;

    /**
     * @var string
     */
    private string $aliasTable = 'a';

    /**
     * @var array
     */
    private static array $cachedAttribute = [];

    protected string $createdColumn = 'created_at';

    protected string $updatedColumn = 'updated_at';

    /**
     * @var bool
     */
    protected bool $updateUpdatedAt = true;

    /**
     * @var bool
     */
    protected bool $forceUpdateUpdatedAt = false;

    /**
     * @var ?Model
     */
    private ?Model $associate = null;

    final public function __construct(Connection|Model $connection)
    {
        if ($connection instanceof Model) {
            $connection = $connection->getConnection();
        }
        $this->queryBuilder = new QueryBuilder($connection);
        $this->connection = $connection;
        self::$globalConnection ??= $connection;
        $this->configure($connection);
        $this->onConstruct();
        parent::__construct($this);
    }

    /**
     * @override
     */
    protected function onConstruct()
    {
    }

    protected function columnAlias(string $column, bool $escape = true) : string
    {
        $columnObj = $this->getObjectTable()->getColumns()->get($column);
        if ($columnObj) {
            $column = $columnObj->getName();
        }
        if ($this->aliasTable) {
            $column = $this->aliasTable . '.' . $column;
        }
        return $escape ? $this->connection->columnQuote(
            $column
        ) : $column;
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
     * @return array
     */
    public function getPrimaryKey() : array
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

    private function configure(Connection $connection) : void
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

        $newColumns = [];
        $availableColumns = [];
        foreach ($objectTable->getColumns() as $column) {
            $columnName = strtolower($column->getName());
            $availableColumns[strtolower($columnName)] = $columnName;
        }
        foreach ($this->columns as $column => $alias) {
            $column = strtolower($column);
            if (array_key_exists($column, $availableColumns)) {
                $newColumns[$availableColumns[$column]] = $alias;
            }
        }
        $this->columns = $newColumns;
        if (empty($this->columns)) {
            $this->columns = $availableColumns;
        }
        $this->table = $objectTable->getName();
        if (!empty($this->primaryKey)) {
            if (count($this->primaryKey) === 1) {
                $this->primaryKey = [
                    reset($this->primaryKey)
                ];
            }
        }

        if (empty($this->primaryKey)) {
            $columns = $objectTable->getColumns();
            foreach ($columns as $column) {
                if ($column->isAutoIncrement()) {
                    $this->primaryKey = [$column->getName()];
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
                    $this->primaryKey = [reset($this->primaryKey)];
                    break;
                }
            }

            if (empty($this->primaryKey) && !empty($unique)) {
                foreach ($unique as $index) {
                    $column = $index->getColumns();
                    $column = $columns->get(reset($column)['name']);
                    if ($column->getType() instanceof Integer) {
                        $this->primaryKey = [$column->getName()];
                        break;
                    }
                }

                if (empty($this->primaryKey)) {
                    /**
                     * @var \Pentagonal\Sso\Core\Database\Schema\Index $index
                     * @noinspection PhpFullyQualifiedNameUsageInspection
                     */
                    $index = reset($unique);
                    $column = $index->getColumns();
                    $this->primaryKey = [reset($column)];
                }
            }
        }

        $primary = [];
        foreach ($this->primaryKey as $value) {
            if (!is_string($value)) {
                continue;
            }
            $lower = strtolower($value);
            if (!isset($availableColumns[$lower])) {
                throw new RuntimeException(
                    sprintf(
                        'Primary Key Column %s Not Found',
                        $value
                    )
                );
            }
            $primary[$lower] = $this->columns[$lower]??$availableColumns[$lower];
        }
        foreach ($availableColumns as $key => $value) {
            if (!isset($this->columns[$key])) {
                $this->columns[$key] = $value;
            }
        }
        $this->primaryKey = $primary;
        if (empty($this->disallowedChange)) {
            $this->disallowedChange = $this->primaryKey;
        }
        if ($this->isFromDatabase()) {
            $qb = $this->getQueryBuilder();
            $exp = $qb->expr();
            foreach ($this->primaryKey as $primary) {
                $value = $this->get($primary);
                if ($value === null) {
                    continue;
                }
                $qb->andWhere(
                    $exp->eq(
                        $this->columnAlias($primary),
                        $qb->createNamedParameter($value)
                    )
                );
            }
        }
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


    /**
     * Implement get() to get data or current fetch
     *
     * @param ?string $key
     * @return mixed|static|false|null
     * @noinspection PhpMissingReturnTypeInspection
     */
    public function get(?string $key = null)
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
        $selects = [];
        foreach ($this->columns as $column => $alias) {
            $selects[] = 'a.'. $column . ' as ' . $alias;
        }
        $this->queryBuilder ??= $this->connection->getQueryBuilder();
        return $this
            ->queryBuilder
            ->select(...$selects)
            ->resetQueryPart('table')
            ->table(
                $this->getTable(),
                $this->aliasTable
            );
    }

    /**
     * @return false|null|static
     */
    public function fetch() : static|null|false
    {
        if ($this->current === false) {
            return false;
        }

        if (!$this->statement) {
            $qb = $this->getQueryBuilder();
            $where = $qb->getQueryPart('where');
            if (empty($where) && ($change = $this->getChangedData())) {
                foreach ($change as $key => $value) {
                    $this->and($key, '=', $value);
                }
            }
            $this->statement = $qb->execute();
        }

        $current = $this->statement->fetchObject(
            $this::class,
            [$this]
        );
        if ($this->current instanceof Model) {
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

    /**
     * @return ?static
     */
    public function first(): ?static
    {
        if ($this->current === null) {
            return $this->fetch()?:null;
        }
        if ($this->incrementResult > 1) {
            return $this->current?:null;
        }
        if ($this->current !== false) {
            $this->incrementResult = 0;
            $this->statement?->closeCursor();
            $this->statement = null;
            $this->current = null;
        }
        return $this->fetch()?:null;
    }

    /**
     * @return ?Model
     */
    public function last(): ?static
    {
        if ($this->current === false) {
            return $this->previous?:null;
        }
        do {
            $this->previous = $this->current;
        } while ($this->fetch() !== false);

        return $this->previous?:null;
    }

    /**
     * @return ?static
     */
    public function next(): ?static
    {
        return $this->fetch()?:null;
    }

    /**
     * @return ?static
     */
    public function prev() : ?static
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

        $lower = strtolower($column);
        if (!isset($this->columns[$lower])) {
            foreach ($this->columns as $key => $alias) {
                if (strtolower($alias) === $lower) {
                    $column = $key;
                    break;
                }
            }
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
                $qb->andWhere($ex->in(
                    $this->columnAlias($column),
                    $inArray
                ));
            }
            return $this;
        }
        foreach ($value as $val) {
            if (!is_iterable($val) && $method !== 'in' && $method !== 'notIn') {
                if ($method === 'isNull'
                    || $method === 'isNotNull'
                ) {
                    $qb->andWhere(
                        $ex->{$method}($this->columnAlias($column))
                    );
                    continue;
                }
                $qb->andWhere(
                    $ex->{$method}($this->columnAlias($column), $qb->createNamedParameter($val))
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
                $qb->andWhere($ex->in($this->columnAlias($column), $inArray));
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
     * @param Connection|null $connection
     * @return static
     */
    public static function find(
        string|int|float|Stringable|array $whereCause = null,
        ?Connection $connection = null
    ) : static {
        $connection ??= self::$globalConnection;
        if (!$connection) {
            throw new RuntimeException(
                'Connection Not Found'
            );
        }
        $model = new static($connection);
        $primaryKey = $model->getPrimaryKey();
        $primary = key($primaryKey);
        if (func_num_args() > 0) {
            $ids = $whereCause;
            if (!is_array($ids)) {
                $ids = [$primary => $ids];
            } elseif (count($primaryKey) === 1) {
                $containString = false;
                foreach ($ids as $key => $value) {
                    if (is_string($key)) {
                        $containString = true;
                        break;
                    }
                }
                if (!$containString) {
                    $ids = [$primary => $ids];
                }
            }

            $ids = array_change_key_case($ids, CASE_LOWER);
            $primaryKey = array_map('strtolower', $primaryKey);
            if (!empty($primaryKey)) {
                foreach ($primaryKey as $value => $alias) {
                    if (!array_key_exists($value, $ids)) {
                        if (!array_key_exists($alias, $ids)) {
                            continue;
                        }
                        $value = $alias;
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
            } elseif (!is_iterable($whereCause)) {
                throw new RuntimeException(
                    'Primary Key Not Found'
                );
            } else {
                foreach ($ids as $key => $value) {
                    $model->and($key, '=', $value);
                }
            }
        }

        return $model;
    }

    public function limit(?int $limit) : static
    {
        $this->getQueryBuilder()->limit($limit);
        return $this;
    }

    public function offset(?int $offset) : static
    {
        $this->getQueryBuilder()->offset($offset);
        return $this;
    }

    public function allowChange(string $column): bool
    {
        $lower = strtolower($column);
        if (!$this->isFromDatabase() && $this->isConstructed()) {
            return isset($this->columns[$lower])
                || in_array($column, $this->columns, true)
                || $this->getObjectTable()->getColumns()->get($column);
        }

        if (isset($this->disallowedChange[$lower])) {
            return false;
        }
        return parent::allowChange($column);
    }

    /**
     * @param string $column
     * @return string
     */
    public function getColumnName(string $column): string
    {
        $lower = strtolower($column);
        if (isset($this->columns[$lower])) {
            return $this->columns[$lower];
        }
        return parent::getColumnName($column);
    }

    /**
     * Validate Column
     *
     * @param string $action
     * @param Column $column
     * @param $value
     */
    protected function filterColumn(string $action, Column $column, $value)
    {
        return $value;
    }

    /**
     * @param array|null $data
     * @return int
     */
    public function insert(?array $data = null): int
    {
        $changedData = $this->getChangedData();
        $data ??= [];
        foreach ($changedData as $key => $datum) {
            $theKey = strtolower($key);
            if (!array_key_exists($theKey, $data)) {
                $data[$key] = $datum;
            }
        }
        $columns = $this->getObjectTable()->getColumns();
        $shouldSet = [];
        $nullable = [];
        foreach ($columns->getColumns() as $column) {
            if ($column->isAutoIncrement()
                || $column->getDefault() !== null
            ) {
                continue;
            }

            $name = strtolower($column->getName());
            $shouldSet[$name] = $column;
            if ($column->isNullable()) {
                $nullable[$name] = null;
            }
        }
        $columnLists = [];
        foreach ($this->columns as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            $value = strtolower($value);
            $columnLists[$value] = $key;
        }

        $createdAtColumn = $columns->get($this->createdColumn);
        if ($createdAtColumn) {
            $lower = strtolower($createdAtColumn->getName());
            if (!isset($data[$lower])) {
                $data[$lower] = $this->connection->createDateFromSQLTimezone();
            }
        }
        $updatedAt = $columns->get($this->updatedColumn);
        if ($updatedAt && $updatedAt->getDefault() === null) {
            $lower = strtolower($updatedAt->getName());
            if (!isset($data[$lower])) {
                $data[$lower] = $updatedAt->isNullable() ? null : '0000-00-00 00:00:00';
            }
        }

        $newData = [];
        foreach ($data as $key => $value) {
            /** @noinspection DuplicatedCode */
            unset($data[$key]);
            if (!is_string($key)) {
                continue;
            }
            $lower = strtolower($key);
            $alternateKey = $columnLists[$lower]??null;
            $column = null;
            if (!($column = $columns->get($key))) {
                if (!$alternateKey) {
                    continue;
                }
                $column = $columns->get($alternateKey);
                if (!$column) {
                    continue;
                }
            }
            $column ??= $columns->get($key);
            $key = $column->getName();
            $key = strtolower($key);
            unset($shouldSet[$key]);
            $value = $this->filterColumn('insert', $column, $value);
            $newData[$key] = $column->getType()->databaseValue($value);
        }
        if (!empty($shouldSet)) {
            foreach ($nullable as $key => $value) {
                unset($shouldSet[$key]);
            }
        }
        if (!empty($shouldSet)) {
            $shouldSet = array_map(fn ($e) => $e->getName(), $shouldSet);
            throw new RuntimeException(
                sprintf(
                    'Column %s is required',
                    implode(', ', $shouldSet)
                )
            );
        }

        $qb = $this
            ->getConnection()
            ->getQueryBuilder()
            ->insert($this->getTable());
        foreach ($newData as $key => $value) {
            $key = $columns->get($key)->getName();
            $qb->setValue($key, $qb->createNamedParameter($value));
        }
        $stmt = $qb->execute();
        $affected = $stmt->rowCount();
        $stmt->closeCursor();
        return $affected;
    }

    public function update(array $data = null): int
    {
        if (!$this->isFromDatabase()) {
            throw new RuntimeException(
                'Update process model should fetch from database first'
            );
        }
        $primaryKeys = $this->getPrimaryKey();
        if (empty($primaryKeys)) {
            throw new RuntimeException(
                'Primary Key Not Found'
            );
        }
        $data ??= [];
        $changedData = $this->getChangedData();
        foreach ($changedData as $key => $datum) {
            $theKey = strtolower($key);
            if (!array_key_exists($theKey, $data)) {
                $data[$key] = $datum;
            }
        }
        $columns = $this->getObjectTable()->getColumns();
        $columnLists = [];
        foreach ($this->columns as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            $value = strtolower($value);
            $columnLists[$value] = $key;
        }
        $updatedAt = $columns->get($this->updatedColumn);
        if ($updatedAt && ($this->updateUpdatedAt || $this->forceUpdateUpdatedAt)) {
            $lower = strtolower($updatedAt->getName());
            // force update
            if ($this->forceUpdateUpdatedAt || !isset($data[$lower])) {
                $data[$lower] = $this->connection->createDateFromSQLTimezone();
            }
        }

        $newData = [];
        foreach ($data as $key => $value) {
            /** @noinspection DuplicatedCode */
            unset($data[$key]);
            if (!is_string($key)) {
                continue;
            }
            $lower = strtolower($key);
            $alternateKey = $columnLists[$lower]??null;
            $column = null;
            if (!($column = $columns->get($key))) {
                if (!$alternateKey) {
                    continue;
                }
                $column = $columns->get($alternateKey);
                if (!$column) {
                    continue;
                }
            }

            $column ??= $columns->get($key);
            $key = $column->getName();
            $key = strtolower($key);
            $value = $this->filterColumn('update', $column, $value);
            $newData[$key] = $column->getType()->databaseValue($value);
        }

        if (empty($newData)) {
            return 0;
        }

        $qb = $this
            ->getConnection()
            ->getQueryBuilder()
            ->update($this->getTable());
        foreach ($newData as $key => $value) {
            $key = $columns->get($key)->getName();
            $key = strtolower($key);
            $qb->setValue($key, $qb->createNamedParameter($value));
        }
        $connection = $this->getConnection();
        $exp = $qb->expr();
        foreach ($primaryKeys as $key => $value) {
            $qb->andWhere(
                $exp->eq(
                    $connection->columnQuote($key),
                    $qb->createNamedParameter($this->get($value))
                )
            );
        }

        $stmt = $qb->execute();
        $affected = $stmt->rowCount();
        $stmt->closeCursor();
        if ($affected > 0) {
            $this->reset();
            $this->changedData = [];
            // re-fetch
            $fetch = $this->fetch();
            if ($fetch) {
                $this->data = $fetch->getData();
                $this->originalData = $fetch->getOriginalData();
            }
        }
        return $affected;
    }

    /**
     * @param array|null $data
     * @return int
     */
    public function save(array $data = null): int
    {
        return $this->isFromDatabase()
            ? $this->update($data)
            : $this->insert($data);
    }

    /**
     * @return ?Model
     */
    public function getAssociate(): ?Model
    {
        return $this->associate;
    }

    /**
     * @template T of Model
     * @param class-string<T>|T $model
     * @return ?T
     */
    public function associateTo(Model|string $model)
    {
        if (!is_a($model, Model::class, true)) {
            return null;
        }
        $object = $this;
        if (!$object->isFromDatabase()) {
            $object = $object->first();
        }
        if (!$object) {
            return null;
        }
        $model = is_string($model) ? new $model($this->getConnection()) : $model;
        $selected = null;
        $identity = null;
        $table = $model->getObjectTable();
        $databaseName = strtolower($object->getConnection()->getDatabaseName());
        $targetTableName = strtolower($object->getObjectTable()->getName());
        foreach ($table->getForeignKeys()->getForeignKeys() as $foreignKey) {
            $refTable = $foreignKey->getReferenceTable();
            if (strtolower($refTable) !== $targetTableName
                || $databaseName !== strtolower($foreignKey->getReferenceDatabase())
            ) {
                continue;
            }
            foreach ($foreignKey->getColumns() as $column) {
                $identity = $object->get($column['referenceColumn']);
                $column   = $column['column'];
                if ($identity === null) {
                    continue;
                }
                $selected = $column;
                break;
            }
        }

        if (!$selected || !$identity) {
            return null;
        }

        $model->associate = $this;
        $model->and($selected, '=', $identity);
        return $model;
    }

    public static function associate(Model $model): ?static
    {
        $object = new static($model);
        $targetTable = $object->getObjectTable();
        $databaseName = strtolower($object->getConnection()->getDatabaseName());
        $targetTableName = strtolower($targetTable->getName());
        $table = $model->getObjectTable();
        $selected = null;
        $identity = null;
        if (!$model->isFromDatabase()) {
            $model = $model->first();
        }

        if (!$model) {
            return null;
        }

        foreach ($table->getForeignKeys()->getForeignKeys() as $foreignKey) {
            $refTable = $foreignKey->getReferenceTable();
            if (strtolower($refTable) !== $targetTableName
                || $databaseName !== strtolower($foreignKey->getReferenceDatabase())
            ) {
                continue;
            }
            foreach ($foreignKey->getColumns() as $column) {
                $identity = $model->get($column['column']);
                $column   = $column['referenceColumn'];
                if ($identity === null) {
                    continue;
                }
                $selected = $column;
                break;
            }
        }

        if (!$selected || !$identity) {
            return null;
        }

        $object->associate = $model;
        $object->and($selected, '=', $identity);
        return $object;
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
    }

    public function __clone(): void
    {
        $this->reset();
        $this->queryBuilder = clone ($this->getQueryBuilder());
    }
}
