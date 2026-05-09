<?php namespace App\home;

class viewer {
    private const ALLOWED = ['csv','json','xml','sql','kml','md'];
    private int $scrollInterval = 300;
    private int $itemPerPage = 50;

    function __construct () {
        global $self;
        $self->takeAll("components");
    }

    function csv ($vars)  { $this->renderCsv($vars); }
    function json ($vars) { $this->render('json', $vars); }
    function xml ($vars)  { $this->render('xml', $vars); }
    function sql ($vars)  { $this->render('sql', $vars); }
    function kml ($vars)  { $this->render('kml', $vars); }
    function md ($vars)   { $this->render('md', $vars); }

    function table ($vars) {
        global $doc;
        if (($vars['format'] ?? '') !== 'csv') return $doc->responseGet(['data' => 'empty']);

        $info = $this->resolveFile('csv', $vars['file'] ?? '');
        if (!$info) return $doc->responseGet(['data' => 'empty']);

        $parsed = $this->parseCsv($info['content']);
        $scroll = max(1, (int) ($vars['scroll'] ?? 1));
        $offset = ($scroll - 1) * $this->scrollInterval;
        $slice  = array_slice($parsed['rows'], $offset, $this->scrollInterval);

        if (empty($slice)) return $doc->responseGet(['data' => 'empty']);

        $result = [];
        foreach ($slice as $i => $row) {
            $result[$i + 1] = $row;
        }
        return $doc->responseGet($result);
    }

    function count ($vars) {
        global $doc;
        if (($vars['format'] ?? '') !== 'csv') {
            return $doc->responseGet((object)['totalRecord' => 0]);
        }

        $info = $this->resolveFile('csv', $vars['file'] ?? '');
        if (!$info) return $doc->responseGet((object)['totalRecord' => 0]);

        $parsed = $this->parseCsv($info['content']);
        return $doc->responseGet((object)['totalRecord' => count($parsed['rows'])]);
    }

    private function renderCsv (array $vars): void {
        global $self, $doc;

        $info = $this->resolveFile('csv', $vars['file'] ?? '');
        if (!$info) {
            header("Location: /home/error?ref=viewer-csv-notfound");
            exit;
        }

        $parsed = $this->parseCsv($info['content']);
        $columns = $parsed['headers'];

        $GLOBALS['vueData']['geturl']         = "/home/viewer/csv/{$info['name']}";
        $GLOBALS['vueData']['itemPerPage']    = $this->itemPerPage;
        $GLOBALS['vueData']['interval']       = [25, 50, 100, $this->scrollInterval];
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
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

    private function render (string $format, array $vars): void {
        global $self, $doc;

        $info = $this->resolveFile($format, $vars['file'] ?? '');
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

    private function parseCsv (string $content): array {
        $headers = [];
        $rows = [];
        $idx = 0;
        $lines = preg_split('/\r\n|\r|\n/', $content);

        foreach ($lines as $line) {
            if ($line === '') continue;
            $cells = str_getcsv($line);
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

    private function resolveFile (string $format, string $name): ?array {
        global $config;

        if (!in_array($format, self::ALLOWED, true)) return null;
        if ($name === '' || preg_match('/[^a-zA-Z0-9_-]/', $name)) return null;

        $base = __DIR__ . "/{$format}";
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
