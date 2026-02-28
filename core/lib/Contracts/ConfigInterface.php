<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Configuration management interface.
 *
 * This interface abstracts configuration access, allowing implementations
 * to load configuration from multiple sources such as XML files, PHP arrays,
 * .env files, or environment variables.
 *
 * @package Gov2lib\Contracts
 */
interface ConfigInterface
{
    /**
     * Get a configuration value by key.
     *
     * Supports dot-notation for nested keys (e.g., "database.host").
     *
     * @param string $key The configuration key.
     * @param mixed $default The default value if key does not exist.
     * @return mixed The configuration value or default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a configuration key exists.
     *
     * @param string $key The configuration key.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool;

    /**
     * Set a configuration value.
     *
     * @param string $key The configuration key.
     * @param mixed $value The value to set.
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get all configuration values.
     *
     * @return array The complete configuration array.
     */
    public function all(): array;

    /**
     * Get the current environment stage.
     *
     * @return string The stage name (e.g., 'development', 'staging', 'production').
     */
    public function getStage(): string;

    /**
     * Get the configured domain name.
     *
     * @return string The domain (e.g., 'example.com').
     */
    public function getDomain(): string;

    /**
     * Get the web root directory path.
     *
     * @return string The absolute or relative path to the web root.
     */
    public function getWebroot(): string;

    /**
     * Get the configured protocol.
     *
     * @return string The protocol (e.g., 'http', 'https').
     */
    public function getProtocol(): string;

    /**
     * Check if the connection is secure (HTTPS).
     *
     * @return bool True if protocol is HTTPS, false otherwise.
     */
    public function isSecure(): bool;
}
