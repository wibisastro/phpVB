<?php

/**
 * Global helper functions.
 *
 * @since 2024-01-16
 */

if (!function_exists('cint')) {
    /**
     * Safely convert a value to integer.
     */
    function cint(mixed $value, int $default = 0): int
    {
        try {
            return intval(floatval($value));
        } catch (\Exception) {
            return $default;
        }
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable value with a default fallback.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('safe_string')) {
    /**
     * Safely cast a value to string, with a default fallback.
     */
    function safe_string(mixed $value, string $default = ''): string
    {
        if ($value === null || $value === false) {
            return $default;
        }

        return (string) $value;
    }
}
