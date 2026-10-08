<?php

namespace Gov2lib;

use Gov2lib\Manifest\Endpoint;
use Gov2lib\Manifest\Parser;

/**
 * Indeks manifest app — daftar MVC yang punya manifest beserta endpoint-nya. Manifest itu sendiri milik tiap MVC:
 * `GET /{app}/{mvc}/manifest` (+ `/readme`, `/release`, `/raw`), disajikan cmd bawaan `manifest` (Gov2lib\Manifest\Endpoint)
 * sehingga ikut authenticate() MVC itu. Indeks ini dipakai agen (MCP phpvb-run) untuk menemukan MVC.
 *
 *   GET /{app}/manifest        → HTML: daftar endpoint (tanpa tombol) | JSON: {mvc: {judul, versi, pola, auth, cmd, url}, tanpa_manifest}
 *   GET /{app}/manifest/ping   → jumlah MVC bermanifest + yang belum (tanpa login)
 *
 * Akses: authenticate('guest') kecuali <manifest>public</manifest> di apps/{app}/xml/config.xml. Tanpa DB, tanpa rahasia.
 * Rujukan: phpVB/rencana/phpvb-manifest-mcp-run.md, release_notes/v5.1.5.md + v5.1.7.md.
 */
class manifest
{
    public function __construct()
    {
        global $self, $vars, $request;

        if (($request ?? '') === 'page' && $self instanceof manifestHandler) {
            try {
                $app = (string) ($vars['app'] ?? '');
                $self->takeAll('components');
                if ($app !== '') {
                    $self->take($app, 'index', 'dependencies');
                    $self->ses->takeAll($app);
                }
            } catch (\Throwable) {
                // app tanpa model index/dependencies: halaman tetap tampil dengan tata letak bawaan
            }
        }
    }

    /** Indeks MVC bermanifest. */
    public function index($vars): mixed
    {
        global $pageID, $request, $doc;

        $m = $this->handler();
        if ($m === null) {
            return $this->tolak(500, 'GALAT_SERVER', 'handler manifest tidak terpasang pada rute ini');
        }
        if (($t = $this->gerbang()) !== null) {
            return $t;
        }
        $daftar = [];
        foreach ($m->daftarManifest() as $mvc => $p) {
            $u = Parser::urai(Endpoint::baca($p));
            $blok = $u['mvc'][$mvc] ?? (count($u['mvc']) === 1 ? reset($u['mvc']) : null);
            $d = $blok['data'] ?? [];
            $daftar[$mvc] = ['judul' => (string) ($d['judul'] ?? $mvc), 'versi' => $m->versiMvc($mvc, $u), 'pola' => array_values((array) ($d['pola'] ?? [])),
                'auth' => $d['auth'] ?? null, 'cmd' => array_keys((array) ($d['cmd'] ?? [])), 'alur' => array_keys((array) ($d['alur'] ?? [])),
                'berkas' => Endpoint::berkas($m->dirApp(), $mvc), 'url' => Endpoint::url((string) $pageID, $mvc), 'galat' => $u['galat']];
        }
        $tanpa = $m->controllerTanpaManifest();
        if (($request ?? '') === 'page') {
            $doc->body('pageTitle', "Manifest API — {$pageID}");
            $doc->body('manifestHtml', markdown::render($this->mdIndeks((string) $pageID, $daftar, $tanpa)));
            $doc->counter();
            if (!isset($doc->content) || !is_array($doc->content)) {
                $doc->content = [];
            }
            $doc->content[$doc->counter] = 'manifest.html';

            return ['ok' => true];
        }

        return ['ok' => true, 'app' => $pageID, 'skema' => Parser::SKEMA, 'jumlah' => count($daftar), 'mvc' => $daftar, 'tanpa_manifest' => $tanpa,
            'url_pola' => "/{$pageID}/{mvc}/manifest[/readme|/release][/raw]"];
    }

    /** Hidup? Berapa MVC bermanifest? — tanpa login, tanpa isi. */
    public function ping($vars): array
    {
        global $pageID;

        $m = $this->handler();
        if ($m === null) {
            return $this->tolak(500, 'GALAT_SERVER', 'handler manifest tidak terpasang pada rute ini');
        }

        return ['ok' => true, 'app' => $pageID, 'agen' => 'manifest', 'skema' => Parser::SKEMA, 'mvc_bermanifest' => array_keys($m->daftarManifest()),
            'tanpa_manifest' => $m->controllerTanpaManifest(), 'url_pola' => "/{$pageID}/{mvc}/manifest", 'cmd' => ['index', 'ping']];
    }

    // ---------------------------------------------------------------- bantu

    private function mdIndeks(string $app, array $daftar, array $tanpa): string
    {
        $b = ["Manifest dimiliki tiap MVC. Endpoint: `/{$app}/{mvc}/manifest` (kontrak API), `…/manifest/readme` (konteks), `…/manifest/release` (riwayat versi), "
            . "tambah `/raw` untuk markdown mentah; `Accept: application/json` untuk data terurai.", ''];
        if ($daftar) {
            $b[] = '| MVC | Judul | Versi | Pola | Endpoint |';
            $b[] = '|---|---|---|---|---|';
            foreach ($daftar as $mvc => $d) {
                $b[] = "| {$mvc} | {$d['judul']} | " . ($d['versi'] ?: '—') . ' | ' . (implode('+', $d['pola']) ?: '—') . " | [{$d['url']['manifest']}]({$d['url']['manifest']}) |";
            }
        } else {
            $b[] = '_Belum ada MVC bermanifest._';
        }
        if ($tanpa) {
            $b[] = '';
            $b[] = 'Belum bermanifest: ' . implode(', ', array_map(fn($x) => "`{$x}`", $tanpa)) . ' — bangkitkan dengan `phpvb_manifest` (gov3phpvb).';
        }

        return implode("\n", $b) . "\n";
    }

    /** authenticate('guest') kecuali app membuka ke publik; null = lolos, array = balasan tolak. */
    private function gerbang(): ?array
    {
        global $self, $doc, $config;

        $mode = strtolower(trim((string) ($config->manifest ?? '')));
        if ($mode === 'public') {
            return null;
        }
        if (isset($self->ses) && $self->ses instanceof gov2session) {
            $self->ses->authenticate('guest');
        }
        if (is_array($doc->error ?? null) && $doc->error !== []) {
            return $doc->responseAuth();
        }

        return null;
    }

    private function handler(): ?manifestHandler
    {
        global $self;

        return $self instanceof manifestHandler ? $self : null;
    }

    private function tolak(int $status, string $code, string $msg, array $extra = []): array
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        return ['ok' => false, 'error' => $code, 'message' => $msg, 'class' => 'is-danger', 'notification' => "{$code}: {$msg}"] + $extra;
    }
}
