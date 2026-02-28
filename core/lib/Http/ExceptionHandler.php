<?php
declare(strict_types=1);

namespace Gov2lib\Http;

use Throwable;

/**
 * ExceptionHandler class that centralizes error and exception handling.
 * 
 * This class replaces scattered try-catch blocks and the old exceptionHandler pattern
 * with a unified, clean architecture approach.
 * 
 * Features:
 * - Registers as global exception and error handler
 * - Distinguishes between page requests (HTML) and AJAX requests (JSON)
 * - Supports typed exception hierarchy for proper HTTP status codes
 * - Maintains backward compatibility with legacy "Code:Message" format
 * - Respects STAGE setting for dev vs prod error detail visibility
 * 
 * Usage:
 *   $handler = new ExceptionHandler($isAjax, $stage);
 *   $handler->register();
 */
class ExceptionHandler
{
    /**
     * Whether the current request is AJAX
     */
    private bool $isAjax;

    /**
     * Current application stage (dev, staging, prod)
     */
    private string $stage;

    /**
     * Previously registered exception handler
     */
    private mixed $previousExceptionHandler = null;

    /**
     * Previously registered error handler
     */
    private mixed $previousErrorHandler = null;

    /**
     * Constructor for ExceptionHandler
     * 
     * @param bool $isAjax Whether the request is AJAX (checked via X-Requested-With header if not provided)
     * @param string $stage Application stage: dev, staging, or prod
     */
    public function __construct(bool $isAjax = false, string $stage = 'prod')
    {
        $this->isAjax = $isAjax ?: $this->detectAjaxRequest();
        $this->stage = $stage;
    }

    /**
     * Register this handler as the global exception and error handler
     * 
     * @return void
     */
    public function register(): void
    {
        $this->previousExceptionHandler = set_exception_handler([$this, 'handle']);
        $this->previousErrorHandler = set_error_handler([$this, 'handleError']);
    }

    /**
     * Unregister this handler and restore previous handlers
     * 
     * @return void
     */
    public function unregister(): void
    {
        if ($this->previousExceptionHandler !== null) {
            set_exception_handler($this->previousExceptionHandler);
        }
        if ($this->previousErrorHandler !== null) {
            set_error_handler($this->previousErrorHandler);
        }
    }

    /**
     * Handle an exception
     * 
     * This is the main exception handler called by set_exception_handler().
     * It renders the exception as either HTML or JSON depending on request type.
     * 
     * @param Throwable $e The exception to handle
     * @return void
     */
    public function handle(Throwable $e): void
    {
        $response = $this->renderException($e);

        // Log the error
        $this->logError($e);

        // Output response based on request type
        if ($this->isAjax) {
            $this->outputJsonResponse($response);
        } else {
            $this->outputHtmlResponse($response);
        }

        exit(1);
    }

    /**
     * Handle a PHP error
     * 
     * This is the error handler callback. It converts PHP errors to exceptions
     * so they can be handled consistently.
     * 
     * @param int $severity Error severity level
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line number where error occurred
     * @return bool True to prevent PHP's internal error handler from running
     */
    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // Don't handle suppressed errors (@)
        if (error_reporting() === 0) {
            return true;
        }

        // Convert PHP error to exception
        $exceptionClass = match ($severity) {
            E_PARSE, E_COMPILE_ERROR, E_COMPILE_WARNING => 'ParseError',
            E_RECOVERABLE_ERROR => 'TypeError',
            E_USER_ERROR => 'RuntimeException',
            default => 'ErrorException',
        };

        $exception = new $exceptionClass($message, 0);

        // Add context
        if (method_exists($exception, 'setFile')) {
            $exception->setFile($file);
        }
        if (method_exists($exception, 'setLine')) {
            $exception->setLine($line);
        }

        $this->handle($exception);

        return true;
    }

    /**
     * Render an exception into an error response array
     * 
     * This method builds a structured response with:
     * - Error code (HTTP status or custom code)
     * - Error message (sanitized in production)
     * - Stack trace (dev only)
     * - Debug information (dev only)
     * 
     * @param Throwable $e The exception to render
     * @return array<string, mixed> The error response
     */
    public function renderException(Throwable $e): array
    {
        // Parse legacy message format if present
        $parsed = self::parseLegacyMessage($e->getMessage());
        
        $code = $parsed['code'] ?? $this->getHttpStatus($e);
        $message = $parsed['message'] ?? $e->getMessage();

        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        // Add details only in dev stage
        if ($this->stage === 'dev') {
            $response['error']['exception'] = get_class($e);
            $response['error']['file'] = $e->getFile();
            $response['error']['line'] = $e->getLine();
            $response['error']['trace'] = $e->getTraceAsString();
        } else {
            // Sanitize message in production
            $response['error']['message'] = match ($code) {
                400, 401, 403, 404 => $message,
                default => 'An error occurred processing your request',
            };
        }

        return $response;
    }

    /**
     * Parse legacy "Code:Message" format from error messages
     * 
     * This provides backward compatibility with the old customException pattern.
     * 
     * Format: "CODE:Error message text"
     * Example: "404:Page not found"
     * 
     * @param string $message The message to parse
     * @return array<string, string|int> Array with 'code' and 'message' keys, or empty if not in legacy format
     */
    public static function parseLegacyMessage(string $message): array
    {
        if (empty($message)) {
            return [];
        }

        // Check for "Code:Message" format
        if (strpos($message, ':') !== false) {
            $parts = explode(':', $message, 2);
            $code = trim($parts[0]);

            // Verify code is numeric
            if (is_numeric($code)) {
                return [
                    'code' => (int)$code,
                    'message' => trim($parts[1] ?? ''),
                ];
            }
        }

        return [];
    }

    /**
     * Get HTTP status code from exception type
     * 
     * Maps exception classes to appropriate HTTP status codes.
     * Checks for HttpException subclasses first.
     * 
     * @param Throwable $e The exception
     * @return int The HTTP status code
     */
    private function getHttpStatus(Throwable $e): int
    {
        // Check for HttpException with status code
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        // Map common exception types to HTTP status codes
        return match (get_class($e)) {
            'HttpNotFoundException', 'NotFoundHttpException' => 404,
            'HttpForbiddenException', 'AccessDeniedHttpException' => 403,
            'HttpUnauthorizedException', 'AuthenticationException' => 401,
            'HttpBadRequestException', 'InvalidArgumentException' => 400,
            'HttpMethodNotAllowedException' => 405,
            'HttpConflictException' => 409,
            'HttpGoneException' => 410,
            'HttpServerErrorException', 'RuntimeException' => 500,
            'HttpNotImplementedException' => 501,
            'HttpServiceUnavailableException' => 503,
            default => 500,
        };
    }

    /**
     * Detect if the request is AJAX
     * 
     * Checks the X-Requested-With header for the common "XMLHttpRequest" value.
     * 
     * @return bool True if AJAX request detected
     */
    private function detectAjaxRequest(): bool
    {
        $header = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower($header) === 'xmlhttprequest';
    }

    /**
     * Output JSON error response
     * 
     * @param array<string, mixed> $response The response array
     * @return void
     */
    private function outputJsonResponse(array $response): void
    {
        header('Content-Type: application/json', true);
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * Output HTML error response
     * 
     * @param array<string, mixed> $response The response array
     * @return void
     */
    private function outputHtmlResponse(array $response): void
    {
        header('Content-Type: text/html; charset=utf-8', true);
        
        $error = $response['error'];
        $code = $error['code'] ?? 500;
        $message = htmlspecialchars($error['message'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8');
        
        http_response_code($code);

        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $code; ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
        }
        .error-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .error-code {
            font-size: 48px;
            font-weight: bold;
            color: #d32f2f;
            margin: 0 0 10px 0;
        }
        .error-message {
            font-size: 18px;
            margin: 0 0 20px 0;
            line-height: 1.6;
        }
        <?php if ($this->stage === 'dev' && !empty($error['trace'])): ?>
        .error-details {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .error-details h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .error-trace {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            line-height: 1.5;
            color: #666;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $code; ?></div>
        <div class="error-message"><?php echo $message; ?></div>
        <?php if ($this->stage === 'dev' && !empty($error['trace'])): ?>
        <div class="error-details">
            <h3>Stack Trace (Development Only)</h3>
            <div class="error-trace"><?php echo htmlspecialchars($error['trace'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
    }

    /**
     * Log error to appropriate handler
     * 
     * @param Throwable $e The exception to log
     * @return void
     */
    private function logError(Throwable $e): void
    {
        $message = sprintf(
            "[%s] %s: %s in %s:%d",
            get_class($e),
            $this->stage,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        // Use PHP's error_log for now (can be replaced with proper logging later)
        error_log($message);
    }
}
