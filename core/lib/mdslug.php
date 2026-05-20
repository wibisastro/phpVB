<?php

namespace Gov2lib;

/**
 * Generic MD-by-slug controller.
 *
 * Renders `apps/{app}/md/{tenant}/{folder}/{slug}.md` for URL
 * `/{app}/{controller}/md/{folder}/{slug}`. Tenant resolution and
 * markdown rendering reuse Gov2lib\document::resolveMD() with an
 * explicit $appOverride so file lookup uses the app from URL segment
 * 1 (not the framework lib directory).
 *
 * Sanitization: folder and slug must match
 * ^[a-zA-Z0-9_-][a-zA-Z0-9._-]*$ — starts with alphanumeric/underscore/
 * dash (forbids leading dot) to block traversal via `..`. Invalid
 * inputs route to a tenant-aware `_invalid.md` if the app provides one.
 */
class mdslug
{
    public function __construct()
    {
        global $self, $vars;
        $app = (string) ($vars['app'] ?? '');
        if ($app !== '') {
            $self->takeAll("components");
            $self->take($app, "index", "dependencies");
        }
    }

    public function index(array $vars = []): void
    {
        global $self, $doc, $pageID;

        $folder = $this->sanitize((string) ($vars['folder'] ?? ''));
        $slug = (string) ($vars['slug'] ?? '');
        $slugClean = $this->sanitize($slug);

        if ($folder === '' || $slugClean === '') {
            $doc->body("pageTitle", "MD — Permintaan Tidak Valid");
            $doc->body("readMD", ($folder !== '' ? $folder . '/' : '') . '_invalid', $pageID);
            $self->content("@{$pageID}/index.html");
            return;
        }

        $doc->body("pageTitle", ucfirst($folder) . " — " . $slugClean);
        $doc->body("readMD", "{$folder}/{$slugClean}", $pageID);
        $self->content("@{$pageID}/index.html");
    }

    private function sanitize(string $s): string
    {
        return preg_match('/^[a-zA-Z0-9_\-][a-zA-Z0-9._\-]*$/', $s) ? $s : '';
    }
}
