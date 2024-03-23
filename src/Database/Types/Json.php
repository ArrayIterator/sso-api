<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Types;

use Pentagonal\Sso\Core\Database\Types\Abstracts\AbstractType;
use function is_array;
use function is_string;
use function json_decode;

class Json extends AbstractType
{
    /**
     * @var string
     */
    public const NAME = 'Json';

    /**
     * @var string
     */
    protected string $columnType = self::JSON;

    /**
     * @inheritDoc
     */
    public function getDeclaration(?int $length = null) : string
    {
        return 'JSON';
    }

    /**
     * @inheritDoc
     */
    public function value($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function databaseValue($value)
    {
        if ($value === 'null') {
            return 'null';
        }
        if (is_string($value) && is_array(json_decode($value, true))) {
            return $value;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
