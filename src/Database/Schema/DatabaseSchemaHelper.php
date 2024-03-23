<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema;

use Pentagonal\Sso\Core\Database\Connection;
use function is_numeric;
use function preg_match;
use function str_contains;
use function strtolower;

final class DatabaseSchemaHelper
{
    /**
     * @var array<string, array<string, Schema>>
     */
    protected static array $schemaCache = [];

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
            $column = new Column($col['COLUMN_NAME']);
            $columns->add($column);
            $precision = $col['NUMERIC_PRECISION']??null;
            $scale = $col['NUMERIC_SCALE']??null;
            $precision = $precision === null ? null : (int) $precision;
            $scale = $scale === null ? null : (int) $scale;
            $characterLength = $col['CHARACTER_MAXIMUM_LENGTH']??null;
            $characterLength = $characterLength === null ? null : (int) $characterLength;
            $extra = $col['EXTRA']?:'';
            $onUpdate = null;
            $onDelete = null;
            if ($extra) {
                preg_match('~on\s+update\s+(\S+)(\s|$)~i', $extra, $match);
                if (isset($match[1])) {
                    $onUpdate = $match[1];
                }
                preg_match('~on\s+delete\s+(\S+)(\s|$)~i', $extra, $match);
                if (isset($match[1])) {
                    $onDelete = $match[1];
                }
            }
            $column
                ->setCollation(
                    $col['COLLATION_NAME']??null
                )->setComment(
                    $col['COLUMN_COMMENT']??null
                )->setAutoIncrement(
                    str_contains($extra, 'auto_increment')
                )->setUnsigned(
                    str_contains($col['COLUMN_TYPE'], 'unsigned')
                )->setZerofill(
                    str_contains($col['COLUMN_TYPE'], 'zerofill')
                )->setPrecision(
                    $precision
                )->setLength(
                    $characterLength??$precision
                )->setNullable(
                    $col['IS_NULLABLE'] === 'YES'
                )->setDefault(
                    $col['COLUMN_DEFAULT']??null
                )->setScale(
                    $scale
                )->setType(
                    $col['DATA_TYPE']
                )->setOnUpdate(
                    $onUpdate
                )->setOnDelete(
                    $onDelete
                )->setOrdinalPosition(
                    (int) $col['ORDINAL_POSITION']
                );
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
                $foreign['CONSTRAINT_NAME']
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
}
