<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Route dispatch result value object.
 *
 * This immutable class encapsulates the result of a route dispatch operation.
 * It provides all necessary information about a matched (or unmatched) route,
 * including status, handler reference, extracted URI variables, and identifiers.
 *
 * @package Gov2lib\Contracts
 */
readonly class RouteResult
{
    /**
     * Constructor.
     *
     * @param RouteStatus $status The dispatch status (FOUND, NOT_FOUND, METHOD_NOT_ALLOWED).
     * @param string $handler The handler or controller reference (e.g., 'UserController@show').
     * @param string $controller The controller class name or reference.
     * @param array $vars Associative array of URI variables extracted from route pattern.
     * @param string $pageId The page identifier from route metadata.
     * @param string $scriptId The script identifier from route metadata.
     * @param string $commandId The command identifier from route metadata.
     */
    public function __construct(
        public RouteStatus $status,
        public string $handler = '',
        public string $controller = '',
        public array $vars = [],
        public string $pageId = '',
        public string $scriptId = '',
        public string $commandId = '',
    ) {}

    /**
     * Check if the route was found and matched.
     *
     * @return bool True if status is FOUND, false otherwise.
     */
    public function isFound(): bool
    {
        return $this->status === RouteStatus::FOUND;
    }
}
