<?php
declare(strict_types=1);

namespace Pentagonal\Sso\App\Models;

use Pentagonal\Sso\Core\Database\Modeller\Model;

class Users extends Model
{
    protected string $table = 'users';
}
