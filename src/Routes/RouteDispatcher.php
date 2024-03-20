<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Routes;

use Closure;
use Pentagonal\Sso\Core\Exceptions\RuntimeException;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteDispatcherInterface;
use Pentagonal\Sso\Core\Routes\Interfaces\RouteInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Stringable;
use Throwable;
use function end;
use function get_class;
use function is_a;
use function is_array;
use function is_object;
use function is_scalar;
use function ob_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function reset;
use function sprintf;
use function strtolower;
use function trim;

readonly class RouteDispatcher implements RouteDispatcherInterface
{
    public function __construct(private ?ContainerInterface $container = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function dispatch(
        callable $callback,
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        array $params = [],
        ?RouteInterface $route = null
    ): ResponseInterface {
        $response = $responseFactory->createResponse();
        $arguments = [
            $request,
            $response,
            $params,
            $route?->getArguments() ?? [],
            $route
        ];
        try {
            // if it was method
            if (is_array($callback)) {
                $obj = reset($callback);
                $method = end($callback);
                $ref = new ReflectionMethod($obj, $method);
            } else {
                $ref = new ReflectionFunction($callback);
            }
            $parameters = $ref->getParameters();
            $newArguments = [];
            $determineArguments = function (ReflectionType $type) use (
                &$determineArguments,
                &$newArguments,
                $request,
                $response,
                $params,
                $route,
                $responseFactory
            ) {
                if ($type->isBuiltin()) {
                    return false;
                }
                if ($type instanceof ReflectionUnionType) {
                    foreach ($type->getTypes() as $t) {
                        if ($determineArguments($t) === true) {
                            return true;
                        }
                    }
                }
                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($typeName === ServerRequestInterface::class
                        || $request::class === $typeName
                        || is_a($request, $typeName)
                    ) {
                        $newArguments[] = $request;
                    } elseif ($typeName === ResponseInterface::class
                        || $response::class === $typeName
                        || is_a($response, $typeName)
                    ) {
                        $newArguments[] = $response;
                    } elseif ($typeName === StreamInterface::class
                        || $response->getBody()::class === $typeName
                        || is_a($response->getBody(), $typeName)
                    ) {
                        $newArguments[] = $response->getBody();
                    } elseif ($typeName === ContainerInterface::class
                        || $this->container::class === $typeName
                        || is_a($this->container, $typeName)
                    ) {
                        $newArguments[] = $this->container;
                    } elseif ($typeName === RouteInterface::class
                        || $route::class === $typeName
                        || is_a($route, $typeName)) {
                        $newArguments[] = $route;
                    } elseif ($typeName === ResponseFactoryInterface::class
                        || $responseFactory::class === $typeName
                        || is_a($responseFactory, $typeName)
                    ) {
                        $newArguments[] = $responseFactory;
                    } elseif ($typeName === UriInterface::class
                        || $request->getUri()::class === $typeName
                        || is_a($request->getUri(), $typeName)
                    ) {
                        $newArguments[] = $request->getUri();
                    } else {
                        return false;
                    }
                    return true;
                }
                return false;
            };

            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                if ($route?->hasArgument($name)) {
                    $newArguments[] = $route->getArgument($name);
                    continue;
                }
                $type = $parameter->getType();
                if ($type && $determineArguments($type) === true) {
                    continue;
                }
                // trim underscore
                $name = trim($name, '_');
                // predefined
                switch (strtolower($name)) {
                    case 'pattern':
                    case 'patterns':
                    case 'routepattern':
                    case 'routepatterns':
                    case 'route_pattern':
                    case 'route_patterns':
                        $newArguments[] = $route?->getPattern() ?? '';
                        continue 2;
                    case 'arguments':
                    case 'routearguments':
                    case 'route_arguments':
                        $newArguments[] = $route?->getArguments() ?? [];
                        continue 2;
                    case 'request':
                    case 'requestinterface':
                    case 'serverrequest':
                    case 'server_request':
                    case 'serverrequestinterface':
                    case 'server_request_interface':
                    case 'request_interface':
                        $newArguments[] = $request;
                        continue 2;
                    case 'response':
                    case 'responseinterface':
                    case 'response_interface':
                        $newArguments[] = $response;
                        continue 2;
                    case 'params':
                    case 'routeparams':
                    case 'route_params':
                    case 'parameter':
                    case 'parameters':
                    case 'route_parameter':
                    case 'route_parameters':
                        $newArguments[] = $params;
                        continue 2;
                    case 'route':
                    case 'routeinterface':
                    case 'route_interface':
                        $newArguments[] = $route;
                        continue 2;
                    case 'containerinterface':
                    case 'container_interface':
                    case 'container':
                        $newArguments[] = $this->container;
                        continue 2;
                    case 'responsefactory':
                    case 'response_factory':
                    case 'responsefactoryinterface':
                    case 'response_factory_interface':
                        $newArguments[] = $responseFactory;
                        continue 2;
                    case 'compiledpatterns':
                    case 'compiledpattern':
                    case 'routecompiledpatterns':
                    case 'routecompiledpattern':
                    case 'route_compiled_pattern':
                    case 'compiled_patterns':
                    case 'compiled_pattern':
                    case 'compiled':
                        $newArguments[] = $route?->getCompiledPattern() ?? '';
                        continue 2;
                    case 'uri':
                    case 'uriinterface':
                    case 'uri_interface':
                        $newArguments[] = $request->getUri();
                        continue 2;
                    case 'stream':
                    case 'streaminterface':
                    case 'responsebody':
                    case 'body':
                    case 'stream_interface':
                    case 'response_body':
                        $newArguments[] = $response->getBody();
                        continue 2;
                    case 'headers':
                    case 'request_headers':
                        $newArguments[] = $request->getHeaders();
                        continue 2;
                    case 'queryparam':
                    case 'query_param':
                    case 'queryparams':
                    case 'query_params':
                        $newArguments[] = $request->getQueryParams();
                        continue 2;
                    case 'querystring':
                    case 'query_string':
                        $newArguments[] = $request->getUri()->getQuery();
                        continue 2;
                    case 'serverparams':
                    case 'serverparam':
                    case 'server_params':
                    case 'server_param':
                        $newArguments[] = $request->getServerParams();
                        continue 2;
                    case 'cookieparams':
                    case 'cookie_params':
                    case 'cookieparam':
                    case 'cookie_param':
                        $newArguments[] = $request->getCookieParams();
                        continue 2;
                    case 'files':
                    case 'uploadfiles':
                    case 'upload_files':
                    case 'uploadedfiles':
                    case 'uploaded_files':
                        $newArguments[] = $request->getUploadedFiles();
                        continue 2;
                }
                if ($parameter->isDefaultValueAvailable()) {
                    $newArguments[] = $parameter->getDefaultValue();
                } else {
                    // fallback to null
                    $newArguments[] = null;
                }
            }
            if (isset($ref) && $route && $callback instanceof Closure) {
                if (!$ref->isStatic() && !$ref->getClosureThis()) {
                    $callback = $callback->bindTo($route);
                }
            }
        } catch (Throwable) {
        }

        $arguments = empty($newArguments) ? $arguments : $newArguments;

        $level = ob_get_level();
        // start buffering
        ob_start();

        $response = $callback(...$arguments);
        if ($response instanceof ResponseInterface) {
            $this->clean($level);
            return $response;
        }

        if ($response instanceof StreamInterface) {
            $this->clean($level);
            return $responseFactory->createResponse()->withBody($response);
        }

        if ($response === null) {
            $response = ob_get_clean();
            if ($level > ob_get_level()) {
                ob_start();
            }
        }

        $this->clean($level);
        if (is_scalar($response) || $response instanceof Stringable) {
            $newResponse = $responseFactory->createResponse();
            $newResponse->getBody()->write((string) $response);
            return $newResponse;
        }
        throw new RuntimeException(
            sprintf(
                'Invalid Response Type %s',
                is_object($response) ? get_class($response) : gettype($response)
            )
        );
    }

    private function clean(int $level) : void
    {
        if ($level < ob_get_level()) {
            ob_clean();
        }
    }
}
