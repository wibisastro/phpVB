<?php

namespace Gov2lib;

/**
 * Markdown renderer — converts .md content to HTML.
 *
 * Requires: league/commonmark ^2.6 (composer require league/commonmark)
 * Fallback: jika library belum terinstall, return plain text dalam <pre>.
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
    private static mixed $converter = null;
    private static bool $available = false;

    private static function getConverter(): mixed
    {
        if (self::$converter === null) {
            if (class_exists(\League\CommonMark\GithubFlavoredMarkdownConverter::class)) {
                self::$converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]);
                self::$available = true;
            }
        }
        return self::$converter;
    }

    /**
     * Render Markdown string ke HTML.
     */
    public static function render(string $markdown): string
    {
        $converter = self::getConverter();
        if (self::$available && $converter) {
            return $converter->convert($markdown)->getContent();
        }
        // Fallback: plain text
        return '<pre style="white-space:pre-wrap; font-family:inherit">' . htmlspecialchars($markdown) . '</pre>';
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
