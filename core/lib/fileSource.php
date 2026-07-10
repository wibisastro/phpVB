<?php

namespace Gov2lib;

/**
 * Unified reader for app-local text data files (tier statis — tanpa database).
 *
 * Resolves files under {appDir}/{format}/ with the same multi-tenant lookup
 * chain as document::body('readMD'):
 *   1. {appDir}/{format}/{tenant}/{name}.{format}
 *   2. {appDir}/{format}/{name}.{format}
 *
 * Path safety: format allowlist, filename pattern ^[a-zA-Z0-9_-]+$, realpath
 * base-dir containment check, per-format size cap from
 * $config->fileSource->maxFileSize (fallback: $config->viewer->maxFileSize,
 * default 1 MiB).
 *
 * @author Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
 * @since 2026-07-10
 */
class fileSource
{
    public const ALLOWED = ['csv', 'json', 'xml', 'sql', 'kml', 'md', 'txt'];
    public const DEFAULT_MAX_SIZE = 1048576;

    /** Reason of the last failed resolve(): 'notfound' | 'toolarge' | null. */
    public ?string $lastError = null;

    /**
     * @param string $appDir App root containing per-format subfolders
     *                       (e.g. apps/home with csv/, json/, md/, ...)
     */
    public function __construct(private string $appDir)
    {
    }

    /**
     * Build a fileSource rooted at apps/{pageID}.
     */
    public static function forApp(string $pageID): self
    {
        return new self(__DIR__ . "/../../apps/{$pageID}");
    }

    /**
     * Resolve and read a file. Returns null on miss (see $lastError).
     *
     * @return array{path: string, name: string, format: string, content: string}|null
     */
    public function resolve(string $format, string $name): ?array
    {
        global $config;

        $this->lastError = null;

        if (!in_array($format, self::ALLOWED, true)) {
            return $this->fail('notfound');
        }

        if ($name === '' || preg_match('/[^a-zA-Z0-9_-]/', $name)) {
            return $this->fail('notfound');
        }

        $baseReal = realpath("{$this->appDir}/{$format}");
        if (!$baseReal) {
            return $this->fail('notfound');
        }

        $real = false;
        $tenant = self::tenant();

        if ($tenant !== '') {
            $real = realpath("{$baseReal}/{$tenant}/{$name}.{$format}");
        }

        if (!$real) {
            $real = realpath("{$baseReal}/{$name}.{$format}");
        }

        if (!$real || !str_starts_with($real, $baseReal)) {
            return $this->fail('notfound');
        }

        $maxSize = (int) (
            $config->fileSource->maxFileSize->{$format}
            ?? $config->fileSource->maxFileSize->default
            ?? $config->viewer->maxFileSize->{$format}
            ?? $config->viewer->maxFileSize->default
            ?? self::DEFAULT_MAX_SIZE
        );

        if (filesize($real) > $maxSize) {
            return $this->fail('toolarge');
        }

        return [
            'path' => $real,
            'name' => $name,
            'format' => $format,
            'content' => file_get_contents($real),
        ];
    }

    /**
     * Resolve + parse in one call. Adds a 'data' key: assoc array for json,
     * headers+rows for csv, SimpleXMLElement for xml/kml, null for raw
     * formats (md/txt/sql).
     *
     * @return array{path: string, name: string, format: string, content: string, data: mixed}|null
     */
    public function read(string $format, string $name): ?array
    {
        $info = $this->resolve($format, $name);

        if (!$info) {
            return null;
        }

        $info['data'] = self::parse($format, $info['content']);

        return $info;
    }

    /**
     * Parse raw content according to format.
     */
    public static function parse(string $format, string $content): mixed
    {
        return match ($format) {
            'csv' => self::parseCsv($content),
            'json' => self::parseJson($content),
            'xml', 'kml' => self::parseXml($content),
            default => null,
        };
    }

    /**
     * Parse CSV: first line is the header row; each row keyed by header,
     * with a synthetic incrementing 'id' column prepended when absent.
     *
     * @return array{headers: string[], rows: array<int, array<string, mixed>>}
     */
    public static function parseCsv(string $content): array
    {
        $headers = [];
        $rows = [];
        $idx = 0;
        $lines = preg_split('/\r\n|\r|\n/', $content);

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $cells = str_getcsv($line, ',', '"', '\\');

            if (empty($headers)) {
                $headers = array_values(array_map('trim', $cells));
                continue;
            }

            $row = ['id' => ++$idx];
            foreach ($headers as $j => $h) {
                $row[$h] = $cells[$j] ?? '';
            }
            $rows[] = $row;
        }

        if (!in_array('id', $headers, true)) {
            array_unshift($headers, 'id');
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Parse JSON into an associative structure. Null on invalid JSON.
     */
    public static function parseJson(string $content): mixed
    {
        return json_decode($content, true);
    }

    /**
     * Parse XML/KML. Null on invalid document.
     */
    public static function parseXml(string $content): ?\SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        libxml_clear_errors();

        return $xml === false ? null : $xml;
    }

    /**
     * Resolve tenant slug for multi-tenant file lookup.
     *
     * Priority: $config->domain->attr['tenant'] → first subdomain label
     * of SERVER_NAME → empty. Result is sanitized to ^[a-z0-9_-]+$.
     */
    public static function tenant(): string
    {
        global $config;

        $tenant = trim((string) ($config->domain->attr['tenant'] ?? ''));

        if ($tenant === '') {
            $serverName = $_SERVER['SERVER_NAME'] ?? '';
            if ($serverName !== '' && str_contains($serverName, '.')) {
                $tenant = strtolower(strtok($serverName, '.'));
            }
        }

        return preg_match('/^[a-z0-9_-]+$/i', $tenant) ? $tenant : '';
    }

    private function fail(string $reason): null
    {
        $this->lastError = $reason;

        return null;
    }
}
