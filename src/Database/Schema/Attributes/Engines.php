<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema\Attributes;

class Engines
{
    public const INNODB = 'InnoDB';
    public const MYISAM = 'MyISAM';
    public const MRG_MYISAM = 'MRG_MyISAM';
    public const MEMORY = 'MEMORY';
    public const CSV = 'CSV';
    public const ARCHIVE = 'Archive';
    public const PERFORMANCE_SCHEMA = 'PERFORMANCE_SCHEMA';
    public const SEQUENCE = 'SEQUENCE';
    public const BLACKHOLE = 'Blackhole';
    public const NDB = 'NDB';
    public const MERGE = 'Merge';
    public const FEDERATED = 'Federated';
    public const EXAMPLE = 'Example';
}
