<?php

/**
 * -----------------------------------
 * Loads global helper functions.
 * 2024-01-16 
 * rijal@cybergl.co.id
 */

if (! function_exists('cint')) {

    /**
     * Convert to integer
     */
    function cint($value, int $default = 0): int
    {
        try {
            return intval(floatval($value));
        } catch (Exception $e) {
            return $default;
        }
    }
}