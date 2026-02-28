<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Template renderer interface.
 *
 * This interface abstracts template rendering, allowing seamless integration
 * with different template engines such as Twig, Blade, or custom implementations.
 * Supports multiple template paths with optional namespacing.
 *
 * @package Gov2lib\Contracts
 */
interface RendererInterface
{
    /**
     * Render a template with provided data.
     *
     * @param string $template The template name or path (e.g., 'user/profile.twig').
     * @param array $data Associative array of variables available to the template.
     * @return string The rendered output as a string.
     */
    public function render(string $template, array $data = []): string;

    /**
     * Add a directory path for template resolution.
     *
     * @param string $path The absolute or relative path to a template directory.
     * @param string $namespace Optional namespace for this path (e.g., 'admin', 'email').
     *                          If empty, this becomes the default search path.
     * @return void
     */
    public function addPath(string $path, string $namespace = ''): void;

    /**
     * Check if a template exists.
     *
     * @param string $template The template name or path.
     * @return bool True if the template exists, false otherwise.
     */
    public function exists(string $template): bool;
}
