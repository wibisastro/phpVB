<?php

declare(strict_types=1);

namespace Gov2lib\Http;

/**
 * HTTP Request wrapper for superglobals.
 * 
 * Provides clean, type-safe access to HTTP request data including GET, POST,
 * cookies, headers, and server variables. Handles URI parsing for route
 * extraction and supports both standard form requests and AJAX/JSON requests.
 */
class Request
{
    /**
     * HTTP request method (GET, POST, PUT, DELETE, etc.)
     */
    public readonly string $method;

    /**
     * Full request URI including query string
     */
    public readonly string $uri;

    /**
     * Page ID parsed from URI
     */
    public readonly string $pageId;

    /**
     * Script ID parsed from URI
     */
    public readonly string $scriptId;

    /**
     * Command ID parsed from URI
     */
    public readonly string $commandId;

    /**
     * GET parameters
     */
    private array $get;

    /**
     * POST parameters
     */
    private array $post;

    /**
     * Cookies
     */
    private array $cookies;

    /**
     * Server variables
     */
    private array $server;

    /**
     * Uploaded files
     */
    private array $files;

    /**
     * Constructor.
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param string $pageId Page ID from URI
     * @param string $scriptId Script ID from URI
     * @param string $commandId Command ID from URI
     * @param array $get GET parameters
     * @param array $post POST parameters
     * @param array $cookies Cookies
     * @param array $server Server variables
     * @param array $files Uploaded files
     */
    public function __construct(
        string $method,
        string $uri,
        string $pageId,
        string $scriptId,
        string $commandId,
        array $get = [],
        array $post = [],
        array $cookies = [],
        array $server = [],
        array $files = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->pageId = $pageId;
        $this->scriptId = $scriptId;
        $this->commandId = $commandId;
        $this->get = $get;
        $this->post = $post;
        $this->cookies = $cookies;
        $this->server = $server;
        $this->files = $files;
    }

    /**
     * Factory method to create Request from current PHP globals.
     * 
     * Parses the current request URI according to the phpVB format:
     * /{webroot}/{pageID}/{scriptID}/{cmdID}
     * 
     * @return self
     */
    public static function createFromGlobals(string $webroot = '/'): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = self::getRequestUri();

        // Parse URI to extract route components
        [$pageId, $scriptId, $commandId] = self::parseUri($uri, $webroot);

        return new self(
            $method,
            $uri,
            $pageId,
            $scriptId,
            $commandId,
            $_GET,
            $_POST,
            $_COOKIE,
            $_SERVER,
            $_FILES
        );
    }

    /**
     * Get the full request URI.
     * 
     * Constructs the URI from REQUEST_URI or uses a fallback based on
     * the current script and query string.
     * 
     * @return string
     */
    private static function getRequestUri(): string
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }

        $uri = $_SERVER['PHP_SELF'] ?? '';
        if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) {
            $uri .= '?' . $_SERVER['QUERY_STRING'];
        }

        return $uri;
    }

    /**
     * Parse URI to extract route components.
     * 
     * Extracts pageID, scriptID, and commandID from URI path.
     * URI format: /{webroot}/{pageID}/{scriptID}/{cmdID}
     * 
     * Example: /phpvb/accounting/ap_vendor/list -> ['accounting', 'ap_vendor', 'list']
     * 
     * @param string $uri
     * @return array [pageId, scriptId, commandId]
     */
    private static function parseUri(string $uri, string $webroot = '/'): array
    {
        $pageId = '';
        $scriptId = '';
        $commandId = '';

        // Remove query string if present
        $path = explode('?', $uri)[0];

        // Strip webroot prefix
        $webroot = rtrim($webroot, '/');
        if ($webroot !== '' && str_starts_with($path, $webroot)) {
            $path = substr($path, strlen($webroot));
        }

        // Split path by forward slash and remove empty segments
        $segments = array_filter(explode('/', $path), fn($seg) => $seg !== '');
        $segments = array_values($segments);

        if (isset($segments[0])) {
            $pageId = $segments[0];
        }
        if (isset($segments[1])) {
            $scriptId = $segments[1];
        }
        if (isset($segments[2])) {
            $commandId = $segments[2];
        }

        return [$pageId, $scriptId, $commandId];
    }

    /**
     * Get input from POST or GET (POST takes precedence).
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    /**
     * Get input from GET parameters.
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Get input from POST parameters.
     * 
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get a cookie value.
     * 
     * @param string $key Cookie name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get a server variable.
     * 
     * @param string $key Server variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get an HTTP header value.
     * 
     * Automatically converts header name to HTTP_ prefixed uppercase form.
     * E.g., 'Content-Type' becomes 'HTTP_CONTENT_TYPE'
     * 
     * @param string $key Header name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$headerKey] ?? $default;
    }

    /**
     * Check if request expects JSON response.
     * 
     * Checks the Accept header for 'application/json'.
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains((string)$accept, 'application/json');
    }

    /**
     * Check if request uses specific HTTP method.
     * 
     * @param string $method HTTP method to check
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Get all input data (POST merged with GET).
     * 
     * POST parameters take precedence over GET parameters with the same name.
     * 
     * @return array
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    /**
     * Get the request payload.
     * 
     * For POST/PUT/DELETE requests, returns POST data.
     * For GET requests, returns GET data.
     * 
     * @return array
     */
    public function getPayload(): array
    {
        return match ($this->method) {
            'POST', 'PUT', 'PATCH', 'DELETE' => $this->post,
            default => $this->get,
        };
    }

    /**
     * Get the command from the request.
     * 
     * First checks POST data for 'cmd' parameter, then falls back to
     * the commandId parsed from the URI.
     * 
     * @return string
     */
    public function getCommand(): string
    {
        return (string)($this->post('cmd') ?? $this->commandId ?? '');
    }

    /**
     * Get uploaded files.
     * 
     * @param string|null $key Specific file key or null for all files
     * @return array
     */
    public function files(?string $key = null): array
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? [];
    }

    /**
     * Check if a file was uploaded.
     * 
     * @param string $key File input name
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }
}
