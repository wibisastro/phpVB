<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Session management interface.
 *
 * This interface abstracts session handling, supporting both JWT tokens
 * and traditional cookie-based sessions. Implementations can vary based
 * on application requirements and authentication mechanisms.
 *
 * @package Gov2lib\Contracts
 */
interface SessionInterface
{
    /**
     * Read and validate a session token.
     *
     * @param string $token The session token (JWT or session ID).
     * @return array The decoded session data.
     */
    public function read(string $token): array;

    /**
     * Save session data.
     *
     * Persists the current session state, optionally triggering a redirect.
     *
     * @param array $data The session data to store.
     * @param int $redirect Optional HTTP status code for redirect (e.g., 302, 303).
     *                       If 0 (default), no redirect occurs.
     * @return void
     */
    public function save(array $data, int $redirect = 0): void;

    /**
     * Reset the current session.
     *
     * Clears all session data and terminates the session.
     *
     * @return void
     */
    public function reset(): void;

    /**
     * Check if the current session represents an authenticated user.
     *
     * @return bool True if user is authenticated, false otherwise.
     */
    public function isAuthenticated(): bool;

    /**
     * Retrieve a value from the session.
     *
     * @param string $key The session key.
     * @param mixed $default The default value if key does not exist.
     * @return mixed The session value or default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the session.
     *
     * @param string $key The session key.
     * @param mixed $value The value to store.
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get the role of the authenticated user.
     *
     * @return string The user role (e.g., 'admin', 'user', 'guest').
     */
    public function getUserRole(): string;

    /**
     * Get the ID of the authenticated user.
     *
     * @return int The user ID.
     */
    public function getUserId(): int;
}
