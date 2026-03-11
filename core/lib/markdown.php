<?php

namespace Gov2lib;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Markdown renderer — converts .md content to HTML.
 *
 * Usage dari controller:
 *   $html = Gov2lib\markdown::render($markdownString);
 *   $html = Gov2lib\markdown::renderFile('/path/to/file.md');
 *
 * Usage dari Twig (via MarkdownExtension):
 *   {{ content|markdown }}
 */
class markdown
{
    private static ?GithubFlavoredMarkdownConverter $converter = null;

    private static function getConverter(): GithubFlavoredMarkdownConverter
    {
        if (self::$converter === null) {
            self::$converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }
        return self::$converter;
    }

    /**
     * Render Markdown string ke HTML.
     */
    public static function render(string $markdown): string
    {
        return self::getConverter()->convert($markdown)->getContent();
    }

    /**
     * Render file .md ke HTML.
     */
    public static function renderFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }
        $content = file_get_contents($filePath);
        return self::render($content);
    }
}
