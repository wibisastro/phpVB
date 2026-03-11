<?php

namespace Gov2lib;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension — menambah filter |markdown untuk render Markdown ke HTML.
 *
 * Registrasi di template.php:
 *   $twig->addExtension(new \Gov2lib\MarkdownExtension());
 *
 * Pakai di template:
 *   {{ markdownContent|markdown }}
 *   {{ markdownContent|markdown|raw }}
 */
class MarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function parseMarkdown(string $content): string
    {
        return markdown::render($content);
    }
}
