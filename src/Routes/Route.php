<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Routes;

use Pentagonal\Sso\Exceptions\InvalidArgumentException;
use Pentagonal\Sso\Routes\Interfaces\RouteInterface;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function explode;
use function in_array;
use function is_numeric;
use function is_string;
use function preg_match;
use function preg_replace_callback;
use function sprintf;
use function strtoupper;
use function trim;

class Route implements RouteInterface
{
    /**
     * @var array $tokens Route Parameters
     */
    protected array $tokens = [];

    /**
     * @var array $arguments Route Arguments for callback
     */
    protected array $arguments = [];

    /**
     * @var array<string> $methods Method list of route
     */
    protected array $methods = [
        self::DEFAULT_METHOD
    ];

    /**
     * @var string $pattern
     */
    protected string $pattern;

    /**
     * @var callable $callback
     */
    protected $callback;

    /**
     * @var ?string $name Route Name
     */
    protected ?string $name = null;

    /**
     * @var ?int $priority Route Priority
     */
    protected ?int $priority = null;

    /**
     * @var ?string $host Route Host
     */
    protected ?string $host = null;

    /**
     * @inheritDoc
     */
    public function __construct(
        array|string $methods,
        string $pattern,
        callable $callback,
        ?string $name = null,
        ?int $priority = null,
        ?string $host = null
    ) {
        $this->setName($name);
        $this->setPriority($priority);
        $this->setHost($host);
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->methods = $this->filterMethods($methods)?:[self::DEFAULT_METHOD];
    }

    /**
     * @inheritDoc
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @inheritDoc
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @inheritDoc
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @inheritDoc
     */
    public function getCallback(): callable
    {
        return $this->callback;
    }

    /**
     * @inheritDoc
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @inheritDoc
     */
    public function setName(?string $name): RouteInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setHost(?string $host): RouteInterface
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setPriority(?int $priority): RouteInterface
    {
        $this->priority = $priority;
        return $this;
    }

    private function assertTokens(array $tokens): void
    {
        foreach ($tokens as $key => $param) {
            if (!is_string($param)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid Param Token %s, must be string',
                        $key
                    )
                );
            }
            if (!is_numeric($key) && !is_string($key)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid Param Token Key %s, must be string',
                        $key
                    )
                );
            }
        }
    }

    public function tokens(array $tokens): RouteInterface
    {
        $this->assertTokens($tokens);
        $this->tokens = $tokens;
        return $this;
    }

    public function token(string $key, string $pattern): RouteInterface
    {
        $this->tokens[$key] = $pattern;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isAllowedMethod(string $method): bool
    {
        $method = strtoupper(trim($method));
        if ($method === '') {
            return false;
        }
        if (in_array('*', $this->methods, true)) {
            return true;
        }
        return in_array($method, $this->methods, true);
    }

    /**
     * Filter Methods, filter methods to be valid
     *
     * @param string|array $methods
     * @return array
     */
    public function filterMethods(string|array $methods): array
    {
        // if data is string, explode by |. Otherwise, use as is
        $methods = is_string($methods)
            ? explode('|', trim($methods)) : $methods;
        $currentMethods = [];
        // iterate methods
        foreach ($methods as $method) {
            if (!is_string($method)) {
                continue;
            }
            $method = trim($method);
            if ($method === '') {
                continue;
            }
            $method = strtoupper($method);
            if ($method === 'ANY') {
                foreach (self::ANY_METHODS as $theMethod) {
                    $currentMethods[$theMethod] = true;
                }
                continue;
            }
            if ($method === self::WILDCARD_METHOD || $method === 'ALL') {
                $currentMethods[self::WILDCARD_METHOD] = self::WILDCARD_METHOD;
                break;
            }
            $currentMethods[$method] = true;
        }
        // return keys
        return array_keys($currentMethods);
    }

    /**
     * @inheritDoc
     */
    public function setArguments(array $variables): RouteInterface
    {
        $this->arguments = $variables;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasArgument(string $key): bool
    {
        return array_key_exists($key, $this->arguments);
    }

    /**
     * @inheritDoc
     */
    public function setArgument(string $key, mixed $value): RouteInterface
    {
        $this->arguments[$key] = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getArgument(string $key): mixed
    {
        return $this->arguments[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @inheritDoc
     */
    public function removeArgument(string $key): RouteInterface
    {
        unset($this->arguments[$key]);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCompiledPattern(): string
    {
        $pattern = $this->getPattern();
        $tokens = array_merge($this->tokens, self::DEFAULT_TOKENS);
        foreach ($tokens as $key => $token) {
            $pattern = str_replace('{' . $key . '}', '(?P<' . $key . '>' . $token . ')', $pattern);
        }
        // if contain { or } and match of placeholder, replace with default token
        if (str_contains($pattern, '{') || str_contains($pattern, '}')) {
            $pattern = preg_replace_callback(
                '~\{([^}]+)}~',
                function ($matches) {
                    $key = $matches[1];
                    // if match of placeholder string compat, group it
                    if (preg_match('~[^a-zA-Z0-9_]~', $matches[1], $matches)) {
                        return '(' . $key . ')';
                    }
                    return '(?P<' . $key . '>' . self::DEFAULT_TOKEN . ')';
                },
                $pattern
            );
        }
        return $pattern;
    }
}
