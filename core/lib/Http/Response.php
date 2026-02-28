<?php

declare(strict_types=1);

namespace Gov2lib\Http;

/**
 * HTTP Response builder for sending JSON, HTML, and other responses.
 * 
 * Provides convenient static methods for building standard responses that are
 * compatible with the phpVB frontend expectations. Handles JSON responses,
 * HTML responses, error formatting, success messages, redirects, and CORS headers.
 */
class Response
{
    /**
     * Send a JSON response and exit.
     * 
     * Sets the appropriate Content-Type header and sends the data as JSON.
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $status HTTP status code (default 200)
     * @return void
     */
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send an HTML response and exit.
     * 
     * Sets the Content-Type to HTML and sends the content.
     * 
     * @param string $content HTML content to send
     * @param int $status HTTP status code (default 200)
     * @return void
     */
    public static function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Build an error response array.
     * 
     * Returns an array formatted for frontend consumption with error details.
     * Compatible with existing phpVB frontend error handling.
     * 
     * @param string $class Error class/type for styling (e.g., 'error', 'warning', 'danger')
     * @param string $message Error message to display
     * @param int $status HTTP status code (default 422)
     * @return array Error response array
     */
    public static function error(string $class, string $message, int $status = 422): array
    {
        return [
            'status' => $status,
            'class' => $class,
            'message' => $message,
            'notification' => $message,
            'success' => false,
        ];
    }

    /**
     * Build a success response array.
     * 
     * Returns an array formatted for frontend consumption with success details.
     * Compatible with existing phpVB frontend success handling. Supports
     * optional callback function name and associated ID for routing.
     * 
     * @param string $message Success message to display
     * @param string $callback Optional callback function to invoke on frontend
     * @param int $id Optional ID associated with the operation (e.g., created record ID)
     * @return array Success response array
     */
    public static function success(string $message, string $callback = '', int $id = 0): array
    {
        return [
            'status' => 200,
            'class' => 'success',
            'message' => $message,
            'notification' => $message,
            'callback' => $callback,
            'id' => $id,
            'success' => true,
        ];
    }

    /**
     * Send a redirect response and exit.
     * 
     * Sets the Location header and HTTP status code, then terminates execution.
     * 
     * @param string $url Target URL for redirect
     * @param int $status HTTP status code (default 302 for temporary redirect)
     * @return void
     */
    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Set CORS (Cross-Origin Resource Sharing) headers.
     * 
     * Configures the response to allow cross-origin requests from any origin.
     * This is useful for API endpoints that need to be accessed from different domains.
     * 
     * Adds the following headers:
     * - Access-Control-Allow-Origin: *
     * - Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
     * - Access-Control-Allow-Headers: Content-Type, Authorization
     * 
     * @return void
     */
    public static function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Send a success JSON response and exit.
     * 
     * Convenience method that combines success() with json() for a complete
     * response cycle.
     * 
     * @param string $message Success message
     * @param string $callback Optional callback function
     * @param int $id Optional ID
     * @param int $status HTTP status code (default 200)
     * @return void
     */
    public static function successJson(
        string $message,
        string $callback = '',
        int $id = 0,
        int $status = 200
    ): void {
        self::json(self::success($message, $callback, $id), $status);
    }

    /**
     * Send an error JSON response and exit.
     * 
     * Convenience method that combines error() with json() for a complete
     * response cycle.
     * 
     * @param string $class Error class/type
     * @param string $message Error message
     * @param int $status HTTP status code (default 422)
     * @return void
     */
    public static function errorJson(
        string $class,
        string $message,
        int $status = 422
    ): void {
        self::json(self::error($class, $message, $status), $status);
    }

    /**
     * Send a plain text response and exit.
     * 
     * @param string $content Text content to send
     * @param int $status HTTP status code (default 200)
     * @return void
     */
    public static function text(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Send no content response (204 No Content).
     * 
     * Used for successful requests that don't need to return any body content.
     * 
     * @return void
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit;
    }

    /**
     * Send an unauthorized response (401).
     * 
     * @param string $message Optional message
     * @return void
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::errorJson('error', $message, 401);
    }

    /**
     * Send a forbidden response (403).
     * 
     * @param string $message Optional message
     * @return void
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::errorJson('error', $message, 403);
    }

    /**
     * Send a not found response (404).
     * 
     * @param string $message Optional message
     * @return void
     */
    public static function notFound(string $message = 'Not found'): void
    {
        self::errorJson('error', $message, 404);
    }
}
