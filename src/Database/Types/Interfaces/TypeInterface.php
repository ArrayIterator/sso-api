<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types\Interfaces;

use Stringable;

interface TypeInterface extends Stringable
{
    public const VARCHAR = 'varchar';
    public const STRING = 'string';
    public const TINYTEXT = 'tinytext';
    public const TEXT = 'text';
    public const MEDIUMTEXT = 'mediumtext';
    public const LONGTEXT = 'longtext';
    public const CHAR = 'char';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const TIMESTAMP = 'timestamp';
    public const TIME = 'time';
    public const YEAR = 'year';
    public const ENUM = 'enum';
    public const SET = 'set';
    public const BINARY = 'binary';
    public const VARBINARY = 'varbinary';
    public const BLOB = 'blob';
    public const TINYBLOB = 'tinyblob';
    public const MEDIUMBLOB = 'mediumblob';
    public const LONGBLOB = 'longblob';
    public const BIT = 'bit';
    public const INT = 'int';
    public const INTEGER = self::INT;
    public const TINYINT = 'tinyint';
    public const SMALLINT = 'smallint';
    public const MEDIUMINT = 'mediumint';
    public const BIGINT = 'bigint';
    public const FLOAT = 'float';
    public const DOUBLE = 'double';
    public const DECIMAL = 'decimal';
    public const REAL = 'real';
    public const NUMERIC = 'numeric';
    public const BOOL = 'bool';
    public const BOOLEAN = self::BOOL;
    public const JSON = 'json';
    public const UUID = 'uuid';
    public const INET_6 = 'inet6';
    public const INET6 = 'inet6';
    public const GEOMETRY = 'geometry';
    public const POINT = 'point';
    public const LINESTRING = 'linestring';
    public const POLYGON = 'polygon';
    public const MULTIPOINT = 'multipoint';
    public const MULTILINESTRING = 'multilinestring';
    public const MULTIPOLYGON = 'multipolygon';
    public const GEOMETRYCOLLECTION = 'geometrycollection';

    public const TYPES = [
        self::VARCHAR,
        self::STRING,
        self::TINYTEXT,
        self::TEXT,
        self::MEDIUMTEXT,
        self::LONGTEXT,
        self::CHAR,
        self::DATE,
        self::DATETIME,
        self::TIMESTAMP,
        self::TIME,
        self::YEAR,
        self::ENUM,
        self::SET,
        self::BINARY,
        self::VARBINARY,
        self::BLOB,
        self::TINYBLOB,
        self::MEDIUMBLOB,
        self::LONGBLOB,
        self::BIT,
        self::INT,
        self::INTEGER,
        self::TINYINT,
        self::SMALLINT,
        self::MEDIUMINT,
        self::BIGINT,
        self::FLOAT,
        self::DOUBLE,
        self::DECIMAL,
        self::REAL,
        self::NUMERIC,
        self::BOOL,
        self::BOOLEAN,
        self::JSON,
        self::UUID,
        self::INET_6,
        self::INET6,
        self::GEOMETRY,
        self::POINT,
        //@todo add polygon
        self::LINESTRING,
        self::POLYGON,
        self::MULTIPOINT,
        self::MULTILINESTRING,
        self::MULTIPOLYGON,
        self::GEOMETRYCOLLECTION,
    ];

    /**
     * Type if length supported
     */
    public const LENGTH_SUPPORTED = [
        self::VARCHAR => [
            'max' => 65535,
            'min' => 1,
            'default' => 255
        ],
        self::CHAR => [
            'max' => 255,
            'min' => 1,
            'default' => 1
        ],
        self::BINARY => [
            'max' => 255,
            'min' => 1,
            'default' => 1
        ],
        self::VARBINARY => [
            'max' => 65535,
            'min' => 1,
            'default' => 255
        ],
        self::ENUM => [
            'max' => 65535,
            'min' => 1,
            'default' => 255
        ],
        self::SET => [
            'max' => 64,
            'min' => 1,
            'default' => 8
        ],
        self::BIT => [
            'max' => 64,
            'min' => 1,
            'default' => 1
        ],
        self::INT => [
            'max' => 11,
            'min' => 11,
            'default' => 11
        ],
        self::TINYINT => [
            'max' => 255,
            'min' => 4,
            'default' => 4
        ],
        self::SMALLINT => [
            'max' => 255,
            'min' => 6,
            'default' => 6
        ],
        self::MEDIUMINT => [
            'max' => 255,
            'min' => 9,
            'default' => 9
        ],
        self::BIGINT => [
            'max' => 255,
            'min' => 20,
            'default' => 20
        ],
        self::FLOAT => [
            'max' => 255,
            'min' => 1,
            'default' => []
        ],
        self::DOUBLE => [
            'max' => 255,
            'min' => 0,
            'default' => []
        ],
        self::DECIMAL => [
            'max' => 65,
            'min' => 0,
            'default' => [10, 0]
        ],
        self::REAL => [
            'max' => 255,
            'min' => 1,
            'default' => []
        ],
        self::NUMERIC => [
            'max' => 255,
            'min' => 1,
            'default' => 10
        ],
    ];

    public function __construct();

    /**
     * @return string type name
     */
    public function getName() : string;

    /**
     * @return bool if length supported
     */
    public function isLengthSupported() : bool;

    /**
     * Value for php
     *
     * @param $value
     */
    public function value($value);

    /**
     * Value for database
     *
     * @param $value
     */
    public function databaseValue($value);

    /**
     * Get database column type
     *
     * @return string
     */
    public function getColumnType() : string;

    /**
     * Get declaration
     *
     * @param ?int $length
     * @return string
     */
    public function getDeclaration(?int $length = null) : string;
}
