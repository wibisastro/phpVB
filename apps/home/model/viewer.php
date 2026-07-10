<?php namespace App\home\model;

class viewer extends \Gov2lib\document {
    public const ALLOWED = ['csv','json','xml','sql','kml','md'];
    public int $scrollInterval = 300;
    public int $itemPerPage = 50;
    private ?\Gov2lib\fileSource $files = null;

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
        return \Gov2lib\fileSource::parseCsv($content);
    }

    function resolveFile (string $format, string $name): ?array {
        if (!in_array($format, self::ALLOWED, true)) return null;

        $files = $this->files ??= new \Gov2lib\fileSource(__DIR__ . "/..");
        $info = $files->resolve($format, $name);

        if (!$info && $files->lastError === 'toolarge') {
            header("Location: /home/error?ref=viewer-{$format}-toolarge");
            exit;
        }

        return $info;
    }
}
