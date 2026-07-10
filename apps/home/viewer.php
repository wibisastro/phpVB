<?php namespace App\home;

class viewer {
    function __construct () {
        global $self;
        $self->takeAll("components");
    }

    function csv ($vars)  { global $self; $self->renderCsv($vars); }
    function json ($vars) { global $self; $self->renderRaw('json', $vars); }
    function xml ($vars)  { global $self; $self->renderRaw('xml', $vars); }
    function sql ($vars)  { global $self; $self->renderRaw('sql', $vars); }
    function kml ($vars)  { global $self; $self->renderRaw('kml', $vars); }
    function md ($vars)   { global $self; $self->renderRaw('md', $vars); }

    function table ($vars) {
        global $self, $doc;
        if (($vars['format'] ?? '') !== 'csv') return $doc->responseGet(['data' => 'empty']);

        $info = $self->resolveFile('csv', $vars['file'] ?? '');
        if (!$info) return $doc->responseGet(['data' => 'empty']);

        $parsed = $self->parseCsv($info['content']);
        $scroll = max(1, (int) ($vars['scroll'] ?? 1));
        $offset = ($scroll - 1) * $self->scrollInterval;
        $slice  = array_slice($parsed['rows'], $offset, $self->scrollInterval);

        if (empty($slice)) return $doc->responseGet(['data' => 'empty']);

        $result = [];
        foreach ($slice as $i => $row) {
            $result[$i + 1] = $row;
        }
        return $doc->responseGet($result);
    }

    function count ($vars) {
        global $self, $doc;
        if (($vars['format'] ?? '') !== 'csv') {
            return $doc->responseGet(['totalRecord' => 0]);
        }

        $info = $self->resolveFile('csv', $vars['file'] ?? '');
        if (!$info) return $doc->responseGet(['totalRecord' => 0]);

        $parsed = $self->parseCsv($info['content']);
        return $doc->responseGet(['totalRecord' => count($parsed['rows'])]);
    }
}
