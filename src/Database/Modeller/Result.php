<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Modeller;

use PDOStatement;
use function debug_backtrace;
use function is_string;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

class Result
{
    /**
     * @var bool
     */
    private bool $fromDatabase = false;

    private bool $constructed = false;

    /**
     * @var array <string, string>
     */
    private array $lowerCaseKeys = [];

    /**
     * @var array <string, mixed>
     */
    private array $data = [];

    /**
     * @var array <string, mixed>
     */
    private array $originalData = [];

    /**
     * @var array <string, mixed>
     */
    private array $changedData = [];

    /**
     * @var Model
     */
    private Model $model;

    /**
     * Result constructor.
     *
     * @param Model $model
     */
    protected function __construct(Model $model)
    {
        $this->model = $model;
        $this->constructed = true;
        $this->reconfigure();
    }

    final public function isFromDatabase() : bool
    {
        return $this->fromDatabase;
    }

    private function reconfigure() : void
    {
        $columns = $this->model->getObjectTable()->getColumns();
        foreach ($this->data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (!($column = $columns->get($key))) {
                continue;
            }
            $val = $column->getType()->value($value);
            if ($val === $value) {
                continue;
            }
            $this->data[$key] = $val;
            $this->originalData[$key] = $value;
        }
    }

    public function getOriginalData(): array
    {
        return $this->originalData;
    }

    public function getModel(): Model
    {
        return clone $this->model;
    }

    public function getLowerCaseKeys(): array
    {
        return $this->lowerCaseKeys;
    }

    final public function __set(string $name, mixed $value): void
    {
        if (!$this->fromDatabase && !$this->constructed) {
            $className = debug_backtrace(
                DEBUG_BACKTRACE_IGNORE_ARGS,
                2
            )[1]['class']??null;
            if ($className && is_a($className, PDOStatement::class, true)) {
                $this->fromDatabase = true;
            }
        }
        if (!$this->fromDatabase && !$this->constructed) {
            return;
        }

        // if not constructed
        if (!$this->constructed) {
            $this->lowerCaseKeys[strtolower($name)] = $name;
            $this->data[$name] = $value;
            return;
        }
        // check if key exists
        if (!$this->has($name)) {
            return;
        }
        $name = $this->lowerCaseKeys[strtolower($name)] ?? null;
        if ($name === null) {
            return;
        }
        if ($this->data[$name] !== $value) {
            $this->changedData[$name] = $value;
        }
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function getChangedData() : array
    {
        return $this->changedData;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function keys() : array
    {
        return array_keys($this->data);
    }

    public function hasKey(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function has(string $key): bool
    {
        if ($this->hasKey($key)) {
            return true;
        }
        $lowerKey = strtolower($key);
        return isset($this->lowerCaseKeys[$lowerKey]);
    }

    public function get(string $key)
    {
        if ($this->hasKey($key)) {
            return $this->data[$key];
        }
        $lowerKey = strtolower($key);
        $key = $this->lowerCaseKeys[$lowerKey] ?? null;
        return $key !== null ? $this->data[$key] : null;
    }
}
