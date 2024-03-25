<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Modeller;

use Pentagonal\Sso\Core\Database\Connection\Statement;

class LazyResult
{
    protected Model $model;

    protected Statement $statement;

    public function __construct(Model $model)
    {
        $this->model = clone $model;
    }

    public function each(callable $function): void
    {
        $this->model->reset();
        while (($row = $this->model->fetch()) instanceof Result) {
            if ($function($row) === false) {
                break;
            }
        }
    }
}
