<?php namespace App\home\model;

class viewer extends \Gov2lib\document {
    public const ALLOWED = ['csv','json','xml','sql','kml','md'];
    public int $scrollInterval = 300;
    public int $itemPerPage = 50;

    function __construct () {
        $this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller=__DIR__."/../".$this->className.".php";
    }

    function dependencies () {
    }

    function renderCsv (array $vars): void {
        global $self, $doc;

        $info = $self->resolveFile('csv', $vars['file'] ?? '');
        if (!$info) {
            header("Location: /home/error?ref=viewer-csv-notfound");
            exit;
        }

        $parsed = $self->parseCsv($info['content']);
        $columns = $parsed['headers'];

        $GLOBALS['vueData']['geturl']         = "/home/viewer/csv/{$info['name']}";
        $GLOBALS['vueData']['itemPerPage']    = $self->itemPerPage;
        $GLOBALS['vueData']['interval']       = [25, 50, 100, $self->scrollInterval];
        $GLOBALS['vueData']['scrollInterval'] = $self->scrollInterval;
        $GLOBALS['vueData']['columns']        = $columns;
        $GLOBALS['vueData']['records']        = count($parsed['rows']);

        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle", "CSV: {$info['name']}");
        $doc->body("filename", $info['name']);

        $self->gov2notification->content();
        $self->gov2search->content();
        $self->content("csv.html");
        $self->gov2pagination->content();
    }

    function renderRaw (string $format, array $vars): void {
        global $self, $doc;

        $info = $self->resolveFile($format, $vars['file'] ?? '');
        if (!$info) {
            header("Location: /home/error?ref=viewer-{$format}-notfound");
            exit;
        }

        $self->take("components","gov2nav", "setDefaultNav");
        $doc->body("pageTitle", strtoupper($format).": {$info['name']}");
        $doc->body("format", $format);
        $doc->body("filename", $info['name']);
        $doc->body("content", $info['content']);
        $self->content("{$format}.html");
    }

    function parseCsv (string $content): array {
        $headers = [];
        $rows = [];
        $idx = 0;
        $lines = preg_split('/\r\n|\r|\n/', $content);

        foreach ($lines as $line) {
            if ($line === '') continue;
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

    function resolveFile (string $format, string $name): ?array {
        global $config;

        if (!in_array($format, self::ALLOWED, true)) return null;
        if ($name === '' || preg_match('/[^a-zA-Z0-9_-]/', $name)) return null;

        $base = __DIR__ . "/../{$format}";
        $real = realpath("{$base}/{$name}.{$format}");
        $baseReal = realpath($base);

        if (!$real || !$baseReal || !str_starts_with($real, $baseReal)) return null;

        $maxSize = (int) (
            $config->viewer->maxFileSize->{$format}
            ?? $config->viewer->maxFileSize->default
            ?? 1048576
        );
        if (filesize($real) > $maxSize) {
            header("Location: /home/error?ref=viewer-{$format}-toolarge");
            exit;
        }

        return [
            'path' => $real,
            'name' => $name,
            'content' => file_get_contents($real),
        ];
    }
}
