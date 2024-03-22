<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Pentagonal\Sso\Core\Database\Connection;
use Traversable;
use function array_map;
use function array_values;
use function strtolower;
use function trim;

class Schema implements IteratorAggregate, Countable, JsonSerializable
{
    protected ?Tables $tables = null;

    /**
     * @var array<string, array<string, Schema>>
     */
    protected static array $tableCache = [];

    private string $database;

    public function __construct(string $database)
    {
        $this->database = $database;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function setTables(Tables $tables) : Schema
    {
        $this->tables = $tables;
        return $this;
    }

    public static function getSchemaFroDatabase(
        Connection $connection,
        string $database
    ) {
        $dsn = $connection->getDSN();
        $lowerDbName = strtolower(trim($database));
        self::$tableCache[$dsn] ??= [];
        $expression = $connection->getExpressionBuilder();
        $schema = self::$tableCache[$dsn][$lowerDbName]??new Schema($database);
        if (!isset(self::$tableCache[$dsn][$lowerDbName])) {
            $tables = new Tables();
            $schema->setTables($tables);
            self::$tableCache[$dsn][$lowerDbName] = $schema;
            $tableLists = [];
            $setDb = false;
            foreach ($connection
                         ->getQueryBuilder()
                         ->from('information_schema.tables', 'tables')
                         ->where($expression->eq('tables.table_schema', '?'))
                         ->orderBy('tables.TABLE_NAME')
                         ->setParameters([$database])
                         ->execute()
                         ->fetchAllAssoc() as $table) {
                if (!$setDb) {
                    $setDb = true;
                    $schema->database = $table['TABLE_SCHEMA'];
                }
                // $table['indexes'] = [];
                $tableLists[] = $table['TABLE_NAME'];
                $table = new Table($table['TABLE_NAME'], $table);
                $table
                    ->setForeignKeys(new ForeignKeys())
                    ->setColumns(new Columns())
                    ->setIndexes(new Indexes());
                $tables->add($table);
            }

            if (count($tables) === 0) {
                return $schema;
            }

            $whereColumns = array_map(fn($v) => '?', $tableLists);
            /*
             * Get Columns
             */
            foreach ($connection
                         ->getQueryBuilder()
                        ->from('information_schema.columns', 'columns')
                        ->where(
                            $expression->eq('columns.table_schema', '?'),
                            $expression->in('columns.table_name', $whereColumns)
                        )->orderBy('columns.TABLE_NAME', 'ASC')
                         ->addOrderBy('columns.ORDINAL_POSITION', 'ASC')
                         ->setParameters([$database, ...array_values($tableLists)])
                         ->execute()
                         ->fetchAllAssoc() as $column) {
                $columns = $tables->get($column['TABLE_NAME'])->getColumns();
                $columns->add(new Column(
                    $column['COLUMN_NAME'],
                    $column
                ));
            }

            /*
             * Get Indexes
             */
            foreach ($connection
                     ->getQueryBuilder()
                     ->from('information_schema.statistics', 'statistics')
                     ->where(
                         $expression->eq('statistics.table_schema', '?'),
                         $expression->in('statistics.table_name', $whereColumns)
                     )->orderBy('statistics.TABLE_NAME', 'ASC')
                     ->addOrderBy('statistics.INDEX_NAME', 'ASC')
                     ->addOrderBy('statistics.SEQ_IN_INDEX', 'ASC')
                     ->setParameters([$database, ...array_values($tableLists)])
                     ->execute()
                     ->fetchAllAssoc() as $index
            ) {
                $objectTable = $tables->get($index['TABLE_NAME']);
                $columns = $objectTable->getColumns();
                $indexes = $objectTable->getIndexes();
                $indexObject = $indexes->get($index['INDEX_NAME'])??new Index(
                    $index['INDEX_NAME']
                );
                $column = $columns->get($index['COLUMN_NAME']);
                $indexes->add($indexObject);
                $indexObject->add(
                    $column,
                    !empty($index['NON_UNIQUE']),
                    (int) $index['SEQ_IN_INDEX'],
                    $index['COLLATION'],
                    (int) $index['CARDINALITY'],
                    $index['SUB_PART'],
                    $index['PACKED'],
                    $index['NULLABLE'],
                    $index['INDEX_TYPE'],
                    ($index['INDEX_COMMENT']??$index['COMMENT'])??null,
                    (bool) $index['IGNORED']
                );
            }

            /*
             * Get Foreign Keys
             */
            foreach ($connection
                     ->getQueryBuilder()
                     ->select(
                         'key_column_usage.*',
                         'table_constraints.*'
                     )
                     ->from('information_schema.key_column_usage', 'key_column_usage')
                     ->where(
                         $expression->eq('key_column_usage.table_schema', '?'),
                         $expression->in('key_column_usage.table_name', $whereColumns),
                         $expression->isNotNull('key_column_usage.referenced_table_name'),
                         $expression->isNotNull('key_column_usage.referenced_column_name')
                     )->orderBy('key_column_usage.TABLE_NAME', 'ASC')
                     ->addOrderBy('key_column_usage.ORDINAL_POSITION', 'ASC')
                     ->innerJoin(
                         'key_column_usage',
                         'information_schema.referential_constraints',
                         'table_constraints',
                         $expression->andX(
                             $expression->eq('key_column_usage.constraint_name', 'table_constraints.constraint_name'),
                             $expression->eq(
                                 'key_column_usage.constraint_schema',
                                 'table_constraints.constraint_schema'
                             )
                         )
                     )
                     ->setParameters([$database, ...array_values($tableLists)])
                     ->execute()
                     ->fetchAllAssoc() as $column) {
                $foreignKeys = $tables->get($column['TABLE_NAME'])->getForeignKeys();
                $foreignKey = $foreignKeys->get(
                    $column['CONSTRAINT_NAME']
                )??new ForeignKey(
                    $column['CONSTRAINT_NAME']
                );
                $databaseName = strtolower($column['REFERENCED_TABLE_SCHEMA']);
                if ($databaseName !== $lowerDbName) {
                    $refTable = self::fromConnection($connection)->getTables()->get($column['REFERENCED_TABLE_NAME']);
                } else {
                    $refTable = $tables->get($column['REFERENCED_TABLE_NAME']);
                }
                $sourceTable = $tables->get($column['TABLE_NAME']);
                $index = $refTable->getIndexes()->get($column['UNIQUE_CONSTRAINT_NAME']);
                $foreignKey->add(
                    $column['CONSTRAINT_SCHEMA'],
                    $sourceTable,
                    $sourceTable->getColumns()->get($column['COLUMN_NAME']),
                    $column['REFERENCED_TABLE_SCHEMA'],
                    $refTable,
                    $refTable->getColumns()->get($column['REFERENCED_COLUMN_NAME']),
                    $column['UPDATE_RULE'],
                    $column['DELETE_RULE'],
                    (int) $column['ORDINAL_POSITION'],
                    $index
                );
                $foreignKeys->add($foreignKey);
            }
        }

        return $schema;
    }

    /**
     * @param Connection $connection
     * @return Schema
     */
    public static function fromConnection(Connection $connection): Schema
    {
        return self::getSchemaFroDatabase($connection, $connection->getDatabaseName());
    }

    /**
     * @return ?Tables
     */
    public function getTables(): ?Tables
    {
        return $this->tables;
    }

    public function getIterator(): Traversable
    {
        return $this->getTables();
    }

    public function count(): int
    {
        return count($this->getTables());
    }

    public function jsonSerialize(): Tables
    {
        return $this->getTables();
    }
}
