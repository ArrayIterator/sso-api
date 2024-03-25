<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Generator;
use Pentagonal\Sso\Core\Database\Connection;
use Pentagonal\Sso\Core\Database\Types\Interfaces\DateTypeInterface;
use Pentagonal\Sso\Core\Database\Types\Interfaces\NumericTypeInterface;
use Pentagonal\Sso\Core\Database\Types\Interfaces\TypeInterface;
use function addcslashes;
use function array_map;
use function implode;
use function in_array;
use function is_numeric;
use function is_string;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function strtoupper;
use function uasort;

final class SchemaHelper
{
    /**
     * @var array<string, array<string, Schema>>
     */
    protected static array $schemaCache = [];

    /**
     * @param Connection $connection
     * @return ?Schema
     */
    public static function getSchema(Connection $connection) : ?Schema
    {
        $dsn = strtolower($connection->getDsn());
        $database = $connection->getDatabaseName();
        self::$schemaCache[$dsn] ??= [];
        $databaseLower = strtolower($database);
        if (!isset(self::$schemaCache[$dsn][$databaseLower])) {
            self::$schemaCache[$dsn][$databaseLower] = self::databaseSchema(
                $connection,
                $database
            );
        }

        return self::$schemaCache[$dsn][$databaseLower];
    }

    /**
     * @param Connection $connection
     * @param string $database
     * @return Schema
     */
    private static function databaseSchema(Connection $connection, string $database): Schema
    {
        $schema = new Schema($connection);
        $tables = $schema->getTables();
        $tableLists = [];
        $expression = $connection->getExpressionBuilder();
        foreach ($connection
             ->getQueryBuilder()
             ->from('information_schema.tables', 'tables')
             ->where($expression->eq('tables.table_schema', '?'))
             ->orderBy('tables.TABLE_NAME')
             ->setParameters([$database])
             ->execute()
             ->fetchAllAssoc() as $def) {
            $table = new Table($def['TABLE_NAME']);
            $tables->add($table);
            $autoIncrement = $def['AUTO_INCREMENT']??null;
            $autoIncrement = $autoIncrement === 'NULL' ? null : (int) $autoIncrement;
            $table
                ->setCollation(
                    $def['TABLE_COLLATION']??null
                )->setEngine(
                    $def['ENGINE']??null
                )->setComment(
                    $def['TABLE_COMMENT']??null
                )->setAutoIncrement(
                    $autoIncrement
                )->setCreateOptions(
                    $def['CREATE_OPTIONS']??null
                )->setTemporary(
                    $def['TABLE_TYPE'] === 'TEMPORARY'
                );
            $tableLists[] = $table->getName();
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
             ->fetchAllAssoc() as $col) {
            $columns = $tables->get($col['TABLE_NAME'])->getColumns();
            $column = new Column($col['COLUMN_NAME'], $col['DATA_TYPE']);
            $columns->add($column);
            $precision = $col['NUMERIC_PRECISION']??null;
            $scale = $col['NUMERIC_SCALE']??null;
            $precision = $precision === null ? null : (int) $precision;
            $scale = $scale === null ? null : (int) $scale;
            $characterLength = $col['CHARACTER_MAXIMUM_LENGTH']??null;
            $characterLength = $characterLength === null ? null : (int) $characterLength;
            $extra = strtolower($col['EXTRA']?:'');
            $column
                ->setCollation(
                    $col['COLLATION_NAME']??null
                )->setComment(
                    $col['COLUMN_COMMENT']??null
                )->setAutoIncrement(
                    str_contains($extra, 'auto_increment')
                )->setPrecision(
                    $precision
                )->setLength(
                    $characterLength??($precision !== null ? ($precision + 1) : null)
                )->setNullable(
                    $col['IS_NULLABLE'] === 'YES'
                )->setDefault(
                    $col['COLUMN_DEFAULT']??null
                )->setScale(
                    $scale
                )->setOrdinalPosition(
                    (int) $col['ORDINAL_POSITION']
                )->setCharset(
                    $col['CHARACTER_SET_NAME']??null
                )->setColumnType(
                    $col['COLUMN_TYPE']
                )->resetAttributes();
            // set extra
            if (str_contains($extra, 'on update')) {
                $column->setOnUpdateCurrentTimestamp();
            } elseif (str_contains($extra, 'binary')) {
                $column->setBinary();
            } elseif (str_contains($extra, 'zerofill')) {
                $column->setZerofill();
            } elseif (str_contains($extra, 'unsigned')) {
                $column->setUnsigned();
            }
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
            $indexes = $objectTable->getIndexes();
            $indexObject = $indexes->get($index['INDEX_NAME'])??new Index(
                $index['INDEX_NAME']
            );
            $indexes->add($indexObject);
            $indexObject->setType(
                $index['INDEX_TYPE']
            )->setUnique(
                empty($index['NON_UNIQUE'])
            )->setComment(
                $index['INDEX_COMMENT']??$index['COMMENT']
            )->setBlockSize(
                null
            );
            $indexObject->addColumn(
                $index['COLUMN_NAME'],
                (int) $index['SEQ_IN_INDEX'],
                (is_numeric($index['SUB_PART']) ? (int) $index['SUB_PART'] : null),
                (int) $index['CARDINALITY'],
                $index['COLLATION']?:null
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
             ->fetchAllAssoc() as $foreign) {
            $foreignKeys = $tables->get($foreign['TABLE_NAME'])->getForeignKeys();
            $foreignKey = $foreignKeys->get(
                $foreign['CONSTRAINT_NAME']
            )??new ForeignKey(
                $foreign['CONSTRAINT_NAME'],
                $foreign['REFERENCED_TABLE_SCHEMA'],
                $foreign['REFERENCED_TABLE_NAME']
            );
            $foreignKeys->add($foreignKey);
            $foreignKey->setOnDelete(
                $foreign['DELETE_RULE']
            )->setOnUpdate(
                $foreign['UPDATE_RULE']
            );
            $foreignKey->addColumn(
                $foreign['COLUMN_NAME'],
                $foreign['REFERENCED_TABLE_NAME'],
                $foreign['REFERENCED_COLUMN_NAME'],
                (int) $foreign['ORDINAL_POSITION']
            );
        }

        return $schema;
    }

    /**
     * @param Connection $connection
     * @param Table $table
     * @param Column $column
     * @param bool $includeAutoIncrement
     * @return string
     */
    private static function createColumnSQL(
        Connection $connection,
        Table $table,
        Column $column,
        bool $includeAutoIncrement = false
    ) : string {
        $sql  = $connection->columnQuote($column->getName());
        $type = $column->getType();
        $sql .= ' ' . $column->getColumnType();

        $tableCollation = $table->getCollation();
        $collation = $column->getCollation();
        $charset = $column->getCharset();
        $collationCharset = null;
        if ($collation) {
            preg_match('~^([^_]+)_~', $tableCollation, $match);
            $collationCharset = $match[1]??null;
        }
        $isDate = $type instanceof DateTypeInterface;
        $isNumeric = $type instanceof NumericTypeInterface;
        if (in_array(
            strtolower($type->getColumnType()),
            TypeInterface::SUPPORTED_COLLATIONS,
            true
        )) {
            if (!$tableCollation
                || $tableCollation !== $collation
                || $collationCharset !== $charset
            ) {
                if ($charset) {
                    $sql .= ' CHARACTER SET ' . $charset;
                }
                if ($collation) {
                    $sql .= ' COLLATE ' . $collation;
                }
            }
        }

        $default = $column->getDefault();
        $isNull = false;
        if ($isDate && $column->isOnUpdateCurrentTimestamp()) {
            $sql .= ' ON UPDATE CURRENT_TIMESTAMP()';
        } elseif ($column->isBinary()) {
            $sql .= ' BINARY';
        } elseif ($column->isCompressed()) {
            $sql .= ' COMPRESSED=zlib'; // zlib
        } elseif ($isNumeric && $column->isUnsigned()) {
            $sql .= ' UNSIGNED';
        } elseif ($isNumeric && $column->isZerofill()) {
            $sql .= ' ZEROFILL';
        }

        if (is_string($default)) {
            preg_match(
                '~^\s*(null|current_timestamp\(\s*\))\s*$~',
                strtolower($default),
                $match
            );
            $IsUUid = false;
            $isDateValue = false;
            if (!empty($match[1])) {
                $isNull = strtoupper($match[1]) === 'NULL';
                $isDateValue = !$isNull && $isDate;
                $default = $isNull
                    ? 'NULL'
                    : ($isDate ? 'CURRENT_TIMESTAMP()' : $default);
            } elseif (preg_match('~^uuid(?:\(\s*\))?\s*$~', strtolower($default))) {
                $IsUUid = true;
                $default = 'UUID()';
            }
            if (! $isDateValue
                && ! $isNull
                && ! $IsUUid
                && !is_numeric($default)
                && !str_starts_with($default, "'")
                && !str_ends_with($default, "'")
            ) {
                $default = $connection->quote($default);
            }

            $sql .= ' DEFAULT ' . $default;
        }

        if ($includeAutoIncrement && $isNumeric && $column->isAutoIncrement()) {
            $sql .= ' AUTO_INCREMENT';
        }
        if (!$isNull && ! $column->isNullable()) {
            $sql .= ' NOT NULL';
        }

        if ($comment = $column->getComment()) {
            $sql .= ' COMMENT ' . $connection->quote($comment);
        }

        return $sql;
    }

    /**
     * @param Schema $schema
     * @return array{
     *     table: array<string, array<string>>,
     *     alter: array<string>
     * }
     */
    public static function getCreateSQL(Schema $schema) : array
    {
        $connection = $schema->getConnection();
        $tableSQL = [];
        $alterSQL = [];
        foreach ($schema->getTables() as $table) {
            $sql = 'CREATE ';
            if ($table->isTemporary()) {
                $sql .= 'TEMPORARY ';
            }
            $sql .= 'TABLE ';
            $sql .= $connection->columnQuote($table->getName());
            $sql .= "(";

            $indexes = $table->getIndexes()->getIndexes();
            if (count($indexes) > 0) {
                $indexSQL = 'ALTER TABLE ' . $connection->columnQuote($table->getName());
                $indexSQLArray = [];
                // create primary key first
                foreach ($indexes as $key => $index) {
                    if (strtoupper($index->getName()) === 'PRIMARY') {
                        $indexSQLArray[] = "ADD PRIMARY KEY ("
                            . implode(
                                ', ',
                                array_map(
                                    fn($v) => $connection->columnQuote($v['name']),
                                    $index->getColumns()
                                )
                            ) . ')';
                        unset($indexes[$key]);
                    }
                }

                // add index and unique index
                foreach ($indexes as $index) {
                    $indexName = $index->getName();
                    $columns = $index->getColumns();
                    uasort($columns, fn($a, $b) => $a['position'] <=> $b['position']);
                    $columns = array_map(fn($v) => $connection->columnQuote($v['name']), $columns);
                    $columns = implode(', ', $columns);
                    $indexName = "`" . addcslashes($indexName, "`") . "`";
                    if ($index->isUnique()) {
                        $indexSQLArray[] = sprintf('ADD UNIQUE INDEX %s(%s)', $indexName, $columns);
                    } else {
                        $indexSQLArray[] = sprintf('ADD INDEX %s(%s)', $indexName, $columns);
                    }
                }
                $indexSQL .= implode(", ", $indexSQLArray);
                $alterSQL[] = $indexSQL;
            }
            $columnSQL = [];
            foreach ($table->getColumns() as $column) {
                $columnSQL[] = self::createColumnSQL($connection, $table, $column);
                if ($column->isAutoIncrement()) {
                    $alterColumn = "ALTER TABLE"
                        . $connection->columnQuote($table->getName())
                        . " MODIFY ";
                    $alterColumn .= self::createColumnSQL(
                        $connection,
                        $table,
                        $column,
                        true
                    );
                    $alterSQL[] = $alterColumn;
                }
            }

            $sql .= implode(", ", $columnSQL);
            $sql .= ")";
            if ($engine = $table->getEngine()) {
                $sql .= ' ENGINE=' . $engine;
            }
            if ($collation = $table->getCollation()) {
                $sql .= ' COLLATE=' . $collation;
            }
            if (($comment = $table->getComment()) !== null) {
                $sql .= ' COMMENT=' . $connection->quote($comment);
            }
            if ($rowFormat = $table->getRowFormat()) {
                $sql .= ' ROW_FORMAT=' . $rowFormat;
            }
            if ($createOptions = $table->getCreateOptions()) {
                $sql .= ' ' . $createOptions;
            }
            $tableSQL[$table->getName()] = $sql;
        }
        $lowerDatabaseName = strtolower($schema->getConnection()->getDatabaseName());
        foreach ($schema->getTables() as $table) {
            foreach ($table->getForeignKeys() as $foreignKey) {
                $foreign = 'ALTER TABLE ' . $connection->columnQuote($table->getName());
                $foreign .= 'ADD CONSTRAINT ' . $connection->columnQuote($foreignKey->getName());
                $foreign .= ' FOREIGN KEY';
                $columns = $foreignKey->getColumns();
                uasort($columns, fn($a, $b) => $a['position'] <=> $b['position']);
                $columnSource = array_map(fn ($e) => $connection->columnQuote($e['column']), $columns);
                $columnRef = array_map(fn ($e) => $connection->columnQuote($e['referenceColumn']), $columns);
                $foreign .= ' (' . implode(', ', $columnSource) . ')';
                $refTable = null;
                $foreign .= ' REFERENCES ';
                if ($lowerDatabaseName !== $foreignKey->getReferenceDatabase()) {
                    $foreign .= ' REFERENCES ' . ($refTable ? $connection->columnQuote($refTable) : '');
                    $foreign .= ".";
                }
                $foreign .= $connection->columnQuote($foreignKey->getReferenceTable());
                $foreign .= ' (' . implode(', ', $columnRef) . ')';
                $foreign .= ' ON DELETE ' . $foreignKey->getOnDelete();
                $foreign .= ' ON UPDATE ' . $foreignKey->getOnUpdate();
                $alterSQL[] = $foreign;
            }
        }

        return [
            'table' => $tableSQL,
            'alter' => $alterSQL,
        ];
    }

    /**
     * @param Schema $schema
     * @param bool $useLock
     * @param bool $dataOnly
     * @return Generator<string|Table>
     */
    public static function export(
        Schema $schema,
        bool $useLock = false,
        bool $dataOnly = false
    ) : Generator {
        $schemaCreate = self::getCreateSQL($schema);
        if (!$dataOnly) {
            foreach ($schemaCreate['table'] as $table) {
                yield $table .';';
            }
        }

        $maxInsert = 1000;
        $connection = $schema->getConnection();
        foreach ($schema->getTables() as $table) {
            $locked = false;
            $query = $connection->getQueryBuilder();
            $query->from($table->getName());
            $stmt = $query->execute();
            $insertQuery = 'INSERT INTO ' . $connection->columnQuote($table->getName()) . ' ';
            $columnLists = array_map(
                fn($v) => $connection->columnQuote($v->getName()),
                $table->getColumns()->getColumns()
            );
            $insertQuery .= '(' . implode(', ', $columnLists) . ')';
            $count = 0;
            $row = $stmt->fetchAssoc();
            do {
                if (!$row) {
                    break;
                }
                if (!$locked) {
                    // table
                    yield $table;

                    if ($useLock) {
                        // lock
                        yield 'LOCK TABLES ' . $connection->columnQuote($table->getName()) . ' WRITE;';
                    }
                    $locked = true;
                }

                $current = $row;
                $row = $stmt->fetchAssoc();
                $addInsertQuery = $count === 0;
                if ($addInsertQuery) {
                    yield $insertQuery .' VALUES ';
                }
                $query = '(';
                $query .= implode(
                    ', ',
                    array_map(
                        static function ($e) use ($connection) {
                            if ($e === null) {
                                return 'NULL';
                            }
                            if (is_string($e) && !str_starts_with($e, 'UUID()')) {
                                return $connection->quote($e);
                            }
                            return $e;
                        },
                        $current
                    )
                );
                $query .= ')';
                $count++;
                if (!$row || ($count % $maxInsert) === 0) {
                    $query .= ';';
                    $count = 0;
                } else {
                    $query .= ','; // add comma
                }
                yield $query;
            } while ($row);
            $stmt->closeCursor();
            if ($locked && $useLock) {
                yield "UNLOCK TABLES;";
            }
        }

        if (!$dataOnly) {
            foreach ($schemaCreate['alter'] as $alter) {
                yield $alter.';';
            }
        }
    }
}
