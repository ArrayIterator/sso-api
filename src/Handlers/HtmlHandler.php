<?php
declare(strict_types=1);

namespace Pentagonal\Sso\Core\Handlers;

use Pentagonal\Sso\Core\Exceptions\HttpMethodNotAllowedException;
use Pentagonal\Sso\Core\Exceptions\HttpNotFoundException;
use Pentagonal\Sso\Core\Exceptions\Interfaces\HttpExceptionInterface;
use Pentagonal\Sso\Core\Handlers\Interfaces\ExceptionHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Stringable;
use Throwable;

class HtmlHandler implements Interfaces\ErrorHandlerInterface
{
    /**
     * Render an HTML error page.
     *
     * @param string $title
     * @param string $message
     * @param Stringable|string|null $trace
     * @return string
     */
    private function render(string $title, string $message, Stringable|string $trace = null): string
    {
        $trace = $trace ? "<pre class='trace'>{$trace}</pre>" : '';
        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>$title</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
            font-size: 16px;
            line-height: 1.5;
        }
        .page {
            margin: 20vh auto;
            width: 720px;
            max-width: 98%;
        }
        pre {
            display: block;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
            overflow-x: auto;
            font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
            font-size: 0.8rem;
        }
        h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        h3 {
            color: #333;
            font-size: 1rem;
            margin-top: 0;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <div class="page">
    <h1>$title</h1>
    <h3>$message</h3>
    {$trace}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Render an HTML error response.
     *
     * @param ExceptionHandlerInterface $handler
     * @param int $statusCode
     * @param string $title
     * @param string $message
     * @param Throwable $trace
     * @return ResponseInterface
     */
    protected function renderResponse(
        ExceptionHandlerInterface $handler,
        int $statusCode,
        string $title,
        string $message,
        Throwable $trace
    ): ResponseInterface {
        $trace = $handler->isDebug() ? $trace : null;
        $html = $this->render($title, $message, $trace);
        return $handler->getResponseFactory()
            ->createResponse($statusCode)
            ->withHeader('Content-Type', 'text/html')
            ->withBody($handler->getStreamFactory()->createStream($html));
    }

    /**
     * @inheritDoc
     */
    public function notFound(ExceptionHandlerInterface $handler, HttpNotFoundException $exception): ResponseInterface
    {
        return $this->renderResponse(
            $handler,
            $exception->getStatusCode(),
            $exception->getTitle(),
            $exception->getMessage(),
            $exception
        );
    }

    /**
     * @inheritDoc
     */
    public function methodNotAllowed(
        ExceptionHandlerInterface $handler,
        HttpMethodNotAllowedException $exception
    ): ResponseInterface {
        return $this->renderResponse(
            $handler,
            $exception->getStatusCode(),
            $exception->getTitle(),
            $exception->getMessage(),
            $exception
        );
    }

    /**
     * @inheritDoc
     */
    public function error(ExceptionHandlerInterface $handler, HttpExceptionInterface $exception): ResponseInterface
    {
        return $this->renderResponse(
            $handler,
            $exception->getStatusCode(),
            $exception->getTitle(),
            $exception->getMessage(),
            $exception
        );
    }

    /**
     * @inheritDoc
     */
    public function exception(
        ExceptionHandlerInterface $handler,
        Throwable $exception,
        ?Throwable $previous = null
    ): ResponseInterface {
        return $this->renderResponse(
            $handler,
            $exception->getCode(),
            $exception->getMessage(),
            $exception->getMessage(),
            $exception
        );
    }
}
