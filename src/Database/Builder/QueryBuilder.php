<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Builder;

use Pentagonal\Sso\Core\Database\Database;
use Pentagonal\Sso\Core\Database\Exceptions\QueryException;
use Pentagonal\Sso\Core\Database\Statement;
use Stringable;
use function array_key_exists;
use function array_unshift;
use function func_get_args;
use function in_array;
use function is_array;
use function str_starts_with;

class QueryBuilder implements Stringable
{
    public const TYPE_SELECT = 0;

    public const TYPE_DELETE = 1;

    public const TYPE_UPDATE = 2;

    public const TYPE_INSERT = 3;

    public const STATE_DIRTY = 0;

    public const STATE_CLEAN = 1;

    protected Database $database;

    protected bool $usePrefix;

    /**
     * @var array{
     *     select: array<string>,
     *     table: array<string>,
     *     join: array<string>,
     *     set: array<string>,
     *     where: array<string|Stringable>,
     *     groupBy: array<string>,
     *     having: array<string|Stringable>,
     *     orderBy: array<string>,
     *     values: array<string|Stringable>
     * }
     */
    protected array $parts = [
        'select' => [],
        'table' => [],
        'join' => [],
        'groupBy' => [],
        'orderBy' => [],
        'values' => [],
        'where' => null,
        'having' => null,
    ];

    private ?string $sql = null;

    private array $parameters = [];

    protected int $type = self::TYPE_SELECT;

    protected int $state = self::STATE_DIRTY;

    protected int $counter = 0;

    protected int $offset = 0;

    protected int $limit = 0;

    protected string $quoteCharacter = '`';

    public function __construct(
        Database $database,
        bool $usePrefix = true
    ) {
        $this->database = $database;
        $this->usePrefix = $usePrefix;
        $this->select('*');
    }

    public function expr(): Expression
    {
        return $this->database->getExpressionBuilder();
    }

    public function getType() : int
    {
        return $this->type;
    }

    public function getState() : int
    {
        return $this->state;
    }

    public function setParameter(
        string|int|float $name,
        string|int|float|null|bool|Stringable $value,
    ) : self {
        $this->parameters[$name] = $value;
        return $this;
    }

    public function setParameters(array $parameters) : self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function offset(int $offset) : self
    {
        $this->state = self::STATE_DIRTY;
        $this->offset = $offset;
        return $this;
    }

    public function limit(int $limit) : self
    {
        $this->state = self::STATE_DIRTY;
        $this->limit = $limit;
        return $this;
    }

    public function add(string $partName, string|array|Stringable $sqlPart, bool $append = false) : self
    {
        // if part is not exists then return
        if (array_key_exists($partName, $this->parts) === false) {
            return $this;
        }

        $isArray = is_array($sqlPart);
        $isMultiple = is_array($this->parts[$partName]);
        if ($isMultiple && !$isArray) {
            $sqlPart = [$sqlPart];
        }
        $this->state = self::STATE_DIRTY;
        if (!$append) {
            $this->parts[$partName] = $sqlPart;
            return $this;
        }

        if (in_array($partName, ['orderBy', 'groupBy', 'select', 'values'])) {
            foreach ($sqlPart as $part) {
                $this->parts[$partName][] = $part;
            }
            return $this;
        }

        if ($isArray && is_array($sqlPart[key($sqlPart)])) {
            $key = key($sqlPart);
            $this->parts[$partName][$key][] = $sqlPart[$key];
        } elseif ($isMultiple) {
            $this->parts[$partName][] = $sqlPart;
        } else {
            $this->parts[$partName] = $sqlPart;
        }

        return $this;
    }

    public function select(string $select = null, string ...$selects) : self
    {
        $this->type = self::TYPE_SELECT;
        if ($select !== null) {
            array_unshift($selects, $select);
        }
        if (empty($select)) {
            return $this;
        }
        $this->add('select', $selects);
        return $this;
    }

    public function addSelect(string $select, string ...$selects) : self
    {
        array_unshift($selects, $select);
        $this->add('select', $selects, true);
        return $this;
    }

    /**
     * Delete
     *
     * @param string|null $table
     * @param string|null $alias
     * @return $this
     */
    public function delete(string $table = null, string $alias = null) : self
    {
        $this->type = self::TYPE_DELETE;
        if (!$table) {
            return $this;
        }

        $this->add('table', [
            'table' => $table,
            'alias' => $alias ?? ''
        ]);
        return $this;
    }

    /**
     * Update
     *
     * @param string|null $table
     * @param string|null $alias
     * @return $this
     */
    public function update(string $table = null, string $alias = null) : self
    {
        $this->type = self::TYPE_UPDATE;
        if (!$table) {
            return $this;
        }
        $this->add('table', [
            'table' => $table,
            'alias' => $alias ?? ''
        ]);
        return $this;
    }

    /**
     * Insert
     *
     * @param string|null $table
     * @param string|null $alias
     * @return $this
     */
    public function insert(string $table = null, string $alias = null) : self
    {
        $this->type = self::TYPE_INSERT;
        if (!$table) {
            return $this;
        }
        $this->add('table', [
            'table' => $table,
            'alias' => $alias ?? ''
        ]);
        return $this;
    }

    /**
     * Set table
     *
     * @param string $table
     * @param string|null $alias
     * @return $this
     */
    public function table(string $table, string $alias = null) : self
    {
        $this->add('table', [
            'table' => $table,
            'alias' => $alias ?? ''
        ], true);
        return $this;
    }

    /**
     * Set FROM
     *
     * @param string $table
     * @param string|null $alias
     * @return $this
     * @see table()
     */
    public function from(string $table, string $alias = null) : self
    {
        return $this->table($table, $alias);
    }

    /**
     * Set JOIN
     *
     * @param string $fromAlias
     * @param string $alias
     * @param string|null $condition
     * @return $this
     */
    public function join(string $fromAlias, string $alias, ?string $condition = null) : self
    {
        return $this->innerJoin($fromAlias, $alias, $condition);
    }

    /**
     * Set INNER JOIN
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|null $condition
     * @return $this
     */
    public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null) : self
    {
        $this->add(
            'join',
            [
                $fromAlias => [
                    'joinType' => 'INNER',
                    'joinTable' => $join,
                    'joinAlias' => $alias,
                    'joinCondition' => $condition,
                ]
            ],
            true
        );
        return $this;
    }

    /**
     * Set LEFT JOIN
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|null $condition
     * @return $this
     */
    public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null) : self
    {
        $this->add(
            'join',
            [
                $fromAlias => [
                    'joinType' => 'LEFT',
                    'joinTable' => $join,
                    'joinAlias' => $alias,
                    'joinCondition' => $condition,
                ]
            ]
        );
        return $this;
    }

    /**
     * Set RIGHT JOIN
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|null $condition
     * @return $this
     */
    public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null) : self
    {
        $this->add(
            'join',
            [
                $fromAlias => [
                    'joinType' => 'RIGHT',
                    'joinTable' => $join,
                    'joinAlias' => $alias,
                    'joinCondition' => $condition,
                ]
            ]
        );
        return $this;
    }

    /**
     * Set value
     *
     * @param string $key
     * @param string|int|float|null|bool|Stringable $value
     * @return $this
     */
    public function setValue(string $key, string|int|float|null|bool|Stringable $value) : self
    {
        $this->add('values', [$key => $value], true);
        return $this;
    }

    /**
     * Set values
     *
     * @param array<string, string|int|float|null|bool|Stringable> $values
     * @return $this
     */
    public function setValues(array $values) : self
    {
        $this->add('values', $values);
        return $this;
    }

    /**
     * Set where
     *
     * @param string|Stringable $where
     * @param string|Stringable ...$wheres
     * @return $this
     */
    public function where(
        string|Stringable $where,
        string|Stringable ...$wheres
    ) : self {
        if (!$where instanceof CompositeExpression) {
            $where = new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [$where]
            );
        }

        $where->addMultiple($wheres);
        $this->add('where', $where);
        return $this;
    }

    /**
     * Set and where
     *
     * @param string|Stringable $where
     * @param string|Stringable ...$wheres
     * @return $this
     */
    public function andWhere(
        string|Stringable $where,
        string|Stringable ...$wheres
    ) : self {
        $args = func_get_args();
        $wherePart = $this->getQueryPart('where');
        if ($wherePart === null) {
            return $this->where(...$args);
        }
        if ($wherePart instanceof CompositeExpression
            && $wherePart->getType() === CompositeExpression::TYPE_AND
        ) {
            $wherePart->addMultiple($args);
        } else {
            if ($wherePart) {
                array_unshift($args, $wherePart);
            }
            $wherePart = new CompositeExpression(
                CompositeExpression::TYPE_AND,
                $args
            );
        }
        $this->add('where', $wherePart, true);
        return $this;
    }

    /**
     * Set or where
     *
     * @param string|Stringable $where
     * @param string|Stringable ...$wheres
     * @return $this
     */
    public function orWhere(
        string|Stringable $where,
        string|Stringable ...$wheres
    ) : self {
        $wherePart = $this->getQueryPart('where');
        $args = func_get_args();
        if ($wherePart instanceof CompositeExpression
            && $wherePart->getType() === CompositeExpression::TYPE_OR
        ) {
            $wherePart->addMultiple($args);
        } else {
            if ($wherePart) {
                array_unshift($args, $wherePart);
            }
            $wherePart = new CompositeExpression(
                CompositeExpression::TYPE_OR,
                $args
            );
        }
        $this->add('where', $wherePart, true);
        return $this;
    }

    /**
     * Set group by
     *
     * @param string $groupBy
     * @param string ...$groupBys
     * @return $this
     */
    public function groupBy(string $groupBy, string ...$groupBys) : self
    {
        array_unshift($groupBys, $groupBy);
        $this->add('groupBy', $groupBys);
        return $this;
    }

    /**
     * Add/append group by
     *
     * @param string $groupBy
     * @param string ...$groupBys
     * @return $this
     */
    public function addGroupBy(string $groupBy, string ...$groupBys) : self
    {
        array_unshift($groupBys, $groupBy);
        $this->add('groupBy', $groupBys, true);
        return $this;
    }

    /**
     * Set having
     *
     * @param string $having
     * @param string ...$havings
     * @return $this
     */
    public function having(string $having, string ...$havings) : self
    {
        if (!$having instanceof CompositeExpression) {
            $having = new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [$having]
            );
        }

        $having->addMultiple($havings);
        $this->add('having', $havings);
        return $this;
    }

    /**
     * Set and having
     *
     * @param string $having
     * @param string ...$havings
     * @return $this
     */
    public function andHaving(string $having, string ...$havings) : self
    {
        /** @noinspection DuplicatedCode */
        $args = func_get_args();
        $havingPart = $this->getQueryPart('having');
        if ($havingPart instanceof CompositeExpression
            && $havingPart->getType() === CompositeExpression::TYPE_AND
        ) {
            $havingPart->addMultiple($args);
        } else {
            if ($havingPart) {
                array_unshift($args, $havingPart);
            }
            $havingPart = new CompositeExpression(
                CompositeExpression::TYPE_AND,
                $args
            );
        }
        $this->add('having', $havingPart, true);
        return $this;
    }

    /**
     * Set or having
     *
     * @param string $having
     * @param string ...$havings
     * @return $this
     */
    public function orHaving(string $having, string ...$havings) : self
    {
        /** @noinspection DuplicatedCode */
        $args = func_get_args();
        $havingPart = $this->getQueryPart('having');
        if ($havingPart instanceof CompositeExpression
            && $havingPart->getType() === CompositeExpression::TYPE_OR
        ) {
            $havingPart->addMultiple($args);
        } else {
            if ($havingPart) {
                array_unshift($args, $havingPart);
            }
            $havingPart = new CompositeExpression(
                CompositeExpression::TYPE_OR,
                $args
            );
        }
        $this->add('having', $havingPart, true);
        return $this;
    }

    /**
     * Set Order by
     *
     * @param string $orderBy
     * @param string $order
     * @return $this
     */
    public function orderBy(string $orderBy, string $order = 'ASC') : self
    {
        $this->add('orderBy', [$orderBy => $order]);
        return $this;
    }

    /**
     * Add/append order by
     *
     * @param string $orderBy
     * @param string $order
     * @return $this
     */
    public function addOrderBy(string $orderBy, string $order = 'ASC') : self
    {
        $this->add('orderBy', [$orderBy => $order], true);
        return $this;
    }

    /**
     * Check if query part exists
     *
     * @param string $queryPart
     * @return bool
     */
    public function hasQueryPart(string $queryPart) : bool
    {
        return isset($this->parts[$queryPart]);
    }

    /**
     * Get query part
     *
     * @param string $queryPart
     * @return array|Stringable|null
     */
    public function getQueryPart(string $queryPart) : array|Stringable|null
    {
        return $this->parts[$queryPart] ?? null;
    }

    /**
     * Get query parts
     *
     * @return array<array<string|Stringable>>
     */
    public function getQueryParts() : array
    {
        return $this->parts;
    }

    /**
     * Reset query part
     *
     * @param string $queryPart
     * @return $this
     */
    public function resetQueryPart(string $queryPart) : self
    {
        if (!isset($this->parts[$queryPart])) {
            return $this;
        }
        $this->state = self::STATE_DIRTY;
        if ($queryPart === 'where' || $queryPart === 'having') {
            $this->parts[$queryPart] = null;
            return $this;
        }
        $this->parts[$queryPart] = [];
        return $this;
    }

    /**
     * @return string SQL query for select
     */
    public function getSQLForSelect() : string
    {
        $fromClauses = [];
        $sql = 'SELECT ' . implode(
            ', ',
            $this->database->columnQuote($this->parts['select'])
        ) . ' FROM';
        foreach ($this->parts['table'] as $from) {
            $fromClause = $this->database->columnQuote($from['table'])
                . ' ' .
                $this->database->columnQuote($from['alias']);
            if (isset($this->parts['join'][$from['alias']])) {
                foreach ($this->parts['join'][$from['alias']] as $join) {
                    $fromClause .= ' ' . $join['joinType'] . ' JOIN ' . $join['joinTable'] . ' ' . $join['joinAlias'];
                    if ($join['joinCondition']) {
                        $fromClause .= ' ON ' . $join['joinCondition'];
                    }
                }
            }
            $fromClauses[$from['alias']] = $fromClause;
        }

        foreach ($this->parts['join'] as $fromAlias => $joins) {
            if (!isset($fromClauses[$fromAlias])) {
                throw new QueryException(
                    sprintf(
                        'Cannot find FROM clause for alias %s',
                        $fromAlias
                    )
                );
            }
        }

        $sql .= ' ' . implode(', ', $fromClauses);
        if ($this->parts['where']) {
            $sql .= ' WHERE ' . $this->parts['where'];
        }
        if ($this->parts['groupBy']) {
            $sql .= ' GROUP BY ' . implode(', ', $this->parts['groupBy']);
        }
        if ($this->parts['having']) {
            $sql .= ' HAVING ' . $this->parts['having'];
        }
        if ($this->parts['orderBy']) {
            $sql .= ' ORDER BY ' . implode(', ', $this->parts['orderBy']);
        }
        if ($this->limit) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        return $sql;
    }

    /**
     * @return string SQL query for update
     */
    public function getSQLForUpdate() : string
    {
        $table = $this->database->columnQuote($this->parts['table']['table'])
            . ' '
            . $this->database->columnQuote($this->parts['table']['alias']);
        $sql = 'UPDATE ' . $table;
        if ($this->parts['values']) {
            $sql .= ' SET ' . implode(', ', $this->parts['values']);
        }
        if ($this->parts['where']) {
            $sql .= ' WHERE ' . $this->parts['where'];
        }
        return $sql;
    }

    /**
     * @return string SQL query for delete
     */
    public function getSQLForDelete() : string
    {
        $table = $this->database->columnQuote($this->parts['table']['table'])
            . ' '
            . $this->database->columnQuote($this->parts['table']['alias']);
        $sql = 'DELETE FROM ' . $table;
        if ($this->parts['where']) {
            $sql .= ' WHERE ' . $this->parts['where'];
        }
        return $sql;
    }

    /**
     * @return string SQL query for insert
     */
    public function getSQLForInsert() : string
    {
        $table = $this->database->columnQuote($this->parts['table']['table']);
        $sql = 'INSERT INTO ' . $table;
        if ($this->parts['values']) {
            $sql .= ' VALUES (' . implode(', ', $this->parts['values']) . ')';
        }
        return $sql;
    }

    /**
     * Get generated SQL
     *
     * @return string
     */
    public function getSQL() : string
    {
        if ($this->sql !== null && $this->state === self::STATE_CLEAN) {
            return $this->sql;
        }
        $sql = match ($this->getType()) {
            self::TYPE_SELECT => $this->getSQLForSelect(),
            self::TYPE_UPDATE => $this->getSQLForUpdate(),
            self::TYPE_DELETE => $this->getSQLForDelete(),
            default => throw new QueryException('No query type has been defined'),
        };
        $this->sql = $sql;
        $this->state = self::STATE_CLEAN;
        return $sql;
    }

    /**
     * Create named parameter
     *
     * @param mixed $value
     * @param string|null $placeholder
     * @return string
     */
    public function createNamedParameter(
        string|int|float|null|bool|Stringable $value,
        string $placeholder = null
    ) : string {
        if ($placeholder === null) {
            $placeholder = 'db_sso'. $this->counter++;
        }
        if (str_starts_with($placeholder, ':')) {
            $placeholder = substr($placeholder, 1);
        }
        $this->setParameter($placeholder, $value);
        return ':' . $placeholder;
    }

    /**
     * Create positional parameter
     *
     * @param string|int|float|bool|Stringable|null $value
     * @return string
     */
    public function createPositionalParameter(
        string|int|float|null|bool|Stringable $value
    ) : string {
        $this->setParameter(++$this->counter, $value);
        return '?';
    }

    /**
     * @return Statement|false false on failure
     */
    public function execute() : Statement|false
    {
        return $this->database->query($this->getSQL(), $this->getParameters());
    }

    /**
     * @return string SQL query
     */
    public function __toString(): string
    {
        return $this->getSQL();
    }
}
