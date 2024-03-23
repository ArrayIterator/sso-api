<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Schema\Attributes;

class RowFormats
{
    public const DYNAMIC = 'Dynamic';
    public const FIXED = 'Fixed';
    public const COMPRESSED = 'Compressed';
    public const REDUNDANT = 'Redundant';
    public const COMPACT = 'Compact';
}
