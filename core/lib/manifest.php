<?php

namespace Gov2lib;

use Gov2lib\Manifest\Parser;

/**
 * Manifest API app — menyajikan tiga dokumen bawaan app hasil vibe coding (gov3phpvb) supaya
 * agen (MCP phpvb-run, pembangun MCP lain) tahu cara memanggil setiap fn controller tanpa
 * membaca source, dan manusia bisa membacanya di browser dengan tata letak app.
 *
 *   GET /{app}/manifest                → HTML (browser) | JSON (Accept: application/json): md + blok data terurai
 *   GET /{app}/manifest/raw            → text/markdown mentah (curl)
 *   GET /{app}/manifest/mvc/{nama}     → satu MVC: md bagian + data
 *   GET /{app}/manifest/release[/raw]  → view/release.md (kronologis versi)
 *   GET /{app}/manifest/readme[/raw]   → README.md (konteks domain)
 *   GET /{app}/manifest/ping           → ada/tidaknya tiga berkas + versi (tanpa login)
 *
 * Akses: authenticate('guest') — harus login (STAGE local terbuka seperti halaman lain); app
 * boleh membuka untuk publik dengan <manifest>public</manifest> di apps/{app}/xml/config.xml.
 * Isinya berkas statis yang ditulis generator/pengembang — tidak ada rahasia, tidak ada DB.
 * Tanpa view/manifest.md → 404 ManifestNotFound (halaman: pemberitahuan).
 *
 * Rujukan: phpVB/rencana/phpvb-manifest-mcp-run.md, release_notes/v5.1.5.md.
 */
class manifest
{
    public function __construct()
    {
        global $self, $vars, $request;

        // Tata letak app (menu, dependensi) hanya perlu untuk halaman — pola Gov2lib\role.
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

    // ---------------------------------------------------------------- cmd

    public function index($vars): mixed
    {
        return $this->sajikan('manifest', $vars);
    }

    /** Hidup? Tiga berkas ada? — tanpa login, tanpa isi. */
    public function ping($vars): array
    {
        global $pageID;

        $m = $this->handler();
        if ($m === null) {
            return $this->tolak(500, 'GALAT_SERVER', 'handler manifest tidak terpasang pada rute ini');
        }
        $berkas = $m->berkas();
        $versi = '';
        if ($berkas['manifest']) {
            $u = Parser::urai($m->baca('manifest'));
            $versi = $u['versi'] !== '' ? $u['versi'] : Parser::versiRelease($m->baca('release'));
        }

        return ['ok' => true, 'app' => $pageID, 'agen' => 'manifest', 'skema' => Parser::SKEMA, 'berkas' => $berkas, 'versi' => $versi,
            'diubah' => $m->diubah('manifest'), 'cmd' => ['index', 'raw', 'mvc', 'release', 'readme', 'ping']];
    }

    public function raw($vars): mixed
    {
        return $this->sajikan('manifest', ['id' => 'raw'] + (is_array($vars) ? $vars : []));
    }

    public function release($vars): mixed
    {
        return $this->sajikan('release', $vars);
    }

    public function readme($vars): mixed
    {
        return $this->sajikan('readme', $vars);
    }

    /** Satu MVC: /{app}/manifest/mvc/{nama}. */
    public function mvc($vars): mixed
    {
        global $pageID, $request;

        $nama = trim((string) (is_array($vars) ? ($vars['id'] ?? '') : ''));
        if (!preg_match(Parser::RE_NAMA, $nama)) {
            return $this->tolak(422, 'MvcTidakValid', 'nama mvc hanya huruf kecil/angka/_');
        }
        $m = $this->handler();
        if ($m === null) {
            return $this->tolak(500, 'GALAT_SERVER', 'handler manifest tidak terpasang pada rute ini');
        }
        if (($t = $this->gerbang()) !== null) {
            return $t;
        }
        if (!$m->ada('manifest')) {
            return $this->tidakAda('manifest');
        }
        $u = Parser::urai($m->baca('manifest'));
        if (!isset($u['mvc'][$nama])) {
            return $this->tolak(404, 'MvcTidakAda', "mvc {$nama} tidak ada di manifest app {$pageID}", ['mvc_tersedia' => array_keys($u['mvc'])]);
        }
        $b = $u['mvc'][$nama];
        if (($request ?? '') === 'page') {
            return $this->halaman('manifest', "Manifest API — {$pageID} / {$nama}", $b['md'], $u['versi']);
        }

        return ['ok' => true, 'app' => $pageID, 'jenis' => 'mvc', 'mvc' => $nama, 'versi' => $u['versi'], 'skema' => Parser::SKEMA,
            'data' => $b['data'], 'markdown' => $b['md'], 'galat' => array_values(array_filter($u['galat'], fn($g) => str_starts_with($g, "MVC {$nama}:")))];
    }

    // ---------------------------------------------------------------- penyajian

    private function sajikan(string $jenis, mixed $vars): mixed
    {
        global $pageID, $request;

        $vars = is_array($vars) ? $vars : [];
        $m = $this->handler();
        if ($m === null) {
            return $this->tolak(500, 'GALAT_SERVER', 'handler manifest tidak terpasang pada rute ini');
        }
        if (($t = $this->gerbang()) !== null) {
            return $t;
        }
        $mentah = (string) ($vars['id'] ?? '') === 'raw';
        if (!$m->ada($jenis)) {
            if (($request ?? '') === 'page' && !$mentah) {
                return $this->halaman($jenis, $this->judul($jenis, $pageID), '', '', true);
            }

            return $this->tidakAda($jenis);
        }
        $md = $m->baca($jenis);
        if ($mentah) {
            return $this->kirimMentah($md, "{$pageID}-{$jenis}.md");
        }
        $u = $jenis === 'manifest' ? Parser::urai($md) : null;
        $versi = $u ? ($u['versi'] !== '' ? $u['versi'] : Parser::versiRelease($m->baca('release'))) : Parser::versiRelease($m->baca('release'));

        if (($request ?? '') === 'page') {
            return $this->halaman($jenis, $this->judul($jenis, $pageID, $versi), $md, $versi);
        }

        $out = ['ok' => true, 'app' => $pageID, 'jenis' => $jenis, 'versi' => $versi, 'skema' => Parser::SKEMA, 'berkas' => $m->berkas(),
            'diubah' => $m->diubah($jenis), 'url' => $this->url($pageID)];
        if ($u) {
            $out['judul'] = $u['judul'];
            $out['app_data'] = $u['app'];
            $out['mvc'] = Parser::ringkas($u);
            $out['data'] = array_map(fn($b) => $b['data'], $u['mvc']);
            $out['galat'] = $u['galat'];
        }
        $out['markdown'] = $md;

        return $out;
    }

    /** Daftarkan konten halaman; balasan array diabaikan dispatcher pada mode page. */
    private function halaman(string $jenis, string $judul, string $md, string $versi, bool $hilang = false): array
    {
        global $self, $doc, $pageID;

        $doc->body('pageTitle', $judul);
        $doc->body('manifestJenis', $jenis);
        $doc->body('manifestVersi', $versi);
        $doc->body('manifestTautan', $this->tautan($pageID, $jenis));
        if ($hilang) {
            $rel = manifestHandler::PATH[$jenis][0] ?? $jenis;
            $doc->body('manifestHtml', '<div class="notification is-warning"><p>Berkas <code>apps/' . htmlspecialchars($pageID, ENT_QUOTES)
                . '/' . htmlspecialchars($rel, ENT_QUOTES) . '</code> belum ada. Bangkitkan dengan tool <code>phpvb_manifest</code> (gov3phpvb) '
                . 'atau tulis sesuai rencana phpvb-manifest-mcp-run.md.</p></div>');
        } else {
            $doc->body('manifestHtml', markdown::render($jenis === 'manifest' ? Parser::tanpaBlokData($md) : $md));
        }
        $self->content('manifest.html');

        return ['ok' => !$hilang];
    }

    /** text/markdown mentah — dipisah agar uji bisa menangkapnya tanpa exit. */
    protected function kirimMentah(string $isi, string $nama): mixed
    {
        if (!headers_sent()) {
            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $nama . '"');
            header('X-Phpvb-Manifest-Skema: ' . Parser::SKEMA);
        }
        echo $isi;
        exit;
    }

    // ---------------------------------------------------------------- gerbang & bantu

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

    private function tidakAda(string $jenis): array
    {
        global $pageID;

        $rel = manifestHandler::PATH[$jenis][0] ?? $jenis;

        return $this->tolak(404, 'ManifestNotFound', "apps/{$pageID}/{$rel} belum ada — bangkitkan dengan phpvb_manifest (gov3phpvb)", ['jenis' => $jenis]);
    }

    private function judul(string $jenis, string $app, string $versi = ''): string
    {
        $v = $versi !== '' ? " v{$versi}" : '';

        return match ($jenis) {
            'release' => "Riwayat rilis — {$app}{$v}",
            'readme' => "README — {$app}",
            default => "Manifest API — {$app}{$v}",
        };
    }

    /** @return array<string, string> */
    private function url(string $app): array
    {
        return ['manifest' => "/{$app}/manifest", 'raw' => "/{$app}/manifest/raw", 'mvc' => "/{$app}/manifest/mvc/{nama}",
            'release' => "/{$app}/manifest/release", 'readme' => "/{$app}/manifest/readme", 'ping' => "/{$app}/manifest/ping"];
    }

    /** @return list<array{label: string, url: string, aktif: bool}> */
    private function tautan(string $app, string $aktif): array
    {
        return [
            ['label' => 'Manifest', 'url' => "/{$app}/manifest", 'aktif' => $aktif === 'manifest'],
            ['label' => 'Riwayat rilis', 'url' => "/{$app}/manifest/release", 'aktif' => $aktif === 'release'],
            ['label' => 'README', 'url' => "/{$app}/manifest/readme", 'aktif' => $aktif === 'readme'],
            ['label' => 'Markdown mentah', 'url' => "/{$app}/manifest/" . ($aktif === 'manifest' ? 'raw' : "{$aktif}/raw"), 'aktif' => false],
        ];
    }

    private function tolak(int $status, string $code, string $msg, array $extra = []): array
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        return ['ok' => false, 'error' => $code, 'message' => $msg, 'class' => 'is-danger', 'notification' => "{$code}: {$msg}"] + $extra;
    }
}
