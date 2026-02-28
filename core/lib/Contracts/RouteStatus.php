<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Route dispatch status enumeration.
 *
 * This enum represents the possible outcomes of a route dispatch operation.
 * It ensures type-safe status checking throughout the routing layer.
 *
 * @package Gov2lib\Contracts
 */
enum RouteStatus: string
{
    /**
     * Route was found and matched successfully.
     */
    case FOUND = 'found';

    /**
     * No matching route was found for the given URI.
     */
    case NOT_FOUND = 'not_found';

    /**
     * A route exists but not for the requested HTTP method.
     */
    case METHOD_NOT_ALLOWED = 'method_not_allowed';
}
