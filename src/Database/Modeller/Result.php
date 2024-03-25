<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Database\Modeller;

use PDOStatement;
use function debug_backtrace;
use function in_array;
use function is_string;
use function strtolower;
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
    protected array $data = [];

    /**
     * @var array <string, mixed>
     */
    protected array $originalData = [];

    /**
     * @var array <string, mixed>
     */
    protected array $changedData = [];

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

    final protected function isConstructed() : bool
    {
        return $this->constructed;
    }

    final public function isFromDatabase() : bool
    {
        return $this->fromDatabase;
    }

    protected function getLowerKeys() : array
    {
        return $this->lowerCaseKeys;
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

    public function allowChange(string $column): bool
    {
        if (!$this->fromDatabase) {
            return false;
        }
        $name = strtolower($column);
        return isset($this->getLowerKeys()[$name])
            || in_array($name, $this->getLowerKeys(), true);
    }

    public function getColumnName(string $column) : string
    {
        $column = strtolower($column);
        return $this->getLowerKeys()[$column] ?? $column;
    }

    public function set(string $name, mixed $value) : static
    {
        $columName = $this->getColumnName($name);
        if (!$this->constructed) {
            $columName = strtolower($columName);
            $this->lowerCaseKeys[$columName] = $columName;
            $this->data[$columName] = $value;
            return $this;
        }
        if (!$this->allowChange($columName)) {
            return $this;
        }
        // check if key exists
        if ($this->isFromDatabase() && !$this->has($columName)) {
            return $this;
        }
        $columName = strtolower($columName);
        $this->changedData[$columName] = $value;
        return $this;
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

        $this->set($name, $value);
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
