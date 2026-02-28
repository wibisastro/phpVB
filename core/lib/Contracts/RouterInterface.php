<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Router interface.
 *
 * This interface defines the contract for URL routing and request dispatch.
 * Implementations handle route matching, HTTP method resolution, and
 * delegation to appropriate handlers or controllers.
 *
 * @package Gov2lib\Contracts
 */
interface RouterInterface
{
    /**
     * Register a route for a specific HTTP method and URI pattern.
     *
     * @param string $method The HTTP method (GET, POST, PUT, DELETE, etc.).
     * @param string $uri The URI pattern (e.g., '/users/{id}').
     * @param string $handler The handler or controller reference (e.g., 'UserController@show').
     * @return void
     */
    public function addRoute(string $method, string $uri, string $handler): void;

    /**
     * Dispatch a request to the appropriate route handler.
     *
     * @param string $method The HTTP method.
     * @param string $uri The requested URI.
     * @return RouteResult The dispatch result containing status, handler, and variables.
     */
    public function dispatch(string $method, string $uri): RouteResult;

    /**
     * Load routes from an XML configuration file.
     *
     * @param string $xmlPath The absolute path to the XML routes file.
     * @return void
     */
    public function loadRoutesFromXml(string $xmlPath): void;

    /**
     * Get the current page identifier.
     *
     * @return string The page ID from the dispatch result.
     */
    public function getPageId(): string;

    /**
     * Get the current script identifier.
     *
     * @return string The script ID from the dispatch result.
     */
    public function getScriptId(): string;

    /**
     * Get the current command identifier.
     *
     * @return string The command ID from the dispatch result.
     */
    public function getCommandId(): string;
}
