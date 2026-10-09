<?php

declare(strict_types=1);

namespace Gov2lib\Manifest;

use Gov2lib\markdown;

/**
 * Artefak per MVC — cmd bawaan `artefak` pada SETIAP controller app (lewat dispatcher core/init/index.php bila
 * controller tidak mendefinisikan method `artefak` sendiri). Endpoint = rute standar MVC itu, jadi konstruktor
 * controller (authenticate, konteks) sudah berjalan: siapa yang boleh membuka MVC, boleh membaca artefaknya.
 *
 *   GET /{app}/{mvc}/artefak                → view/{mvc}/readme.md     (konteks domain MVC — akar)
 *   GET /{app}/{mvc}/artefak/manifes        → view/{mvc}/manifest.md   (kontrak API MVC ini, untuk agen/MCP)
 *   GET /{app}/{mvc}/artefak/rilis          → view/{mvc}/release.md    (kronologis versi MVC)
 *   GET /{app}/{mvc}/artefak/spek           → view/{mvc}/spec.md       (prompt spesifikasi yang menghasilkan MVC ini — salinan beku tiket)
 *   …/berkas (mis. /artefak/berkas, /artefak/spek/berkas) → text/markdown mentah (salin = salin berkas)
 *   POST /{app}/{mvc} {"cmd":"artefak","jenis":"spek","format":"berkas"} — setara
 *
 * Nama berkas tidak berubah (readme/manifest/release/spec.md); hanya segmen URL yang berbahasa Indonesia. Segmen lama
 * (manifest/release/spec/raw) tetap diterima. Alias deprecated: cmd `readme` (v5.1.8, akar readme) dan cmd `manifest`
 * (v5.1.7, akar manifest.md: `/{app}/{mvc}/manifest[/readme|/release|/spec][/raw]`).
 * Balasan mengikuti Accept: HTML (halaman, tanpa tombol — navigasi lewat endpoint), JSON (markdown + blok data terurai).
 * Rujukan: phpVB/rencana/sdd-berbasis-tiket.md, release_notes/v5.1.9.md (v5.1.8: DONE_phpvb-readme-spec-mvc.md).
 *
 * @since 2026-10-08 (v5.1.7; v5.1.8 = akar readme + spec; v5.1.9 = akar artefak, segmen Indonesia)
 */
final class Endpoint
{
    public const CMD = 'artefak';
    /** Alias deprecated: `readme` (v5.1.8, akar readme) dan `manifest` (v5.1.7, akarnya manifest.md). */
    public const CMD_LAMA = ['readme', 'manifest'];
    public const CMDS = [self::CMD, 'readme', 'manifest'];
    /** Jenis internal (= nama berkas); segmen URL-nya lihat SEGMEN. */
    public const JENIS = ['readme', 'manifest', 'release', 'spec'];
    /** Segmen URL → jenis internal / format `raw`. Segmen v5.1.9 berbahasa Indonesia; segmen v5.1.7–5.1.8 tetap diterima. */
    public const SEGMEN = ['manifes' => 'manifest', 'rilis' => 'release', 'spek' => 'spec', 'berkas' => 'raw',
        'readme' => 'readme', 'manifest' => 'manifest', 'release' => 'release', 'spec' => 'spec', 'raw' => 'raw'];
    /** Jenis internal → segmen URL v5.1.9. */
    public const SEGMEN_JENIS = ['readme' => '', 'manifest' => 'manifes', 'release' => 'rilis', 'spec' => 'spek', 'raw' => 'berkas'];
    public const BERKAS = [
        'readme' => ['readme.md', 'README.md'],
        'manifest' => ['manifest.md'],
        'release' => ['release.md', 'CHANGELOG.md'],
        'spec' => ['spec.md'],
    ];

    /** Pengganti pengirim mentah (uji): fn(string $isi, string $nama): mixed. Null = header + echo + exit. */
    public static ?\Closure $kirimMentah = null;

    /** @return array{0: string, 1: string} [akar app (apps/{app}), nama MVC (= nama class controller)] */
    public static function lokasi(object $controller): array
    {
        $rc = new \ReflectionClass($controller);
        $file = (string) $rc->getFileName();

        return [dirname($file), $rc->getShortName()];
    }

    public static function path(string $dirApp, string $mvc, string $jenis): ?string
    {
        if (!preg_match(Parser::RE_NAMA, $mvc) && !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $mvc)) {
            return null;
        }
        foreach (self::BERKAS[$jenis] ?? [] as $nama) {
            $p = "{$dirApp}/view/{$mvc}/{$nama}";
            if (is_file($p) && is_readable($p)) {
                return $p;
            }
        }

        return null;
    }

    /** @return array<string, bool> */
    public static function berkas(string $dirApp, string $mvc): array
    {
        $out = [];
        foreach (self::JENIS as $j) {
            $out[$j] = self::path($dirApp, $mvc, $j) !== null;
        }

        return $out;
    }

    /** Jenis & format dari segmen rute (id, scroll) atau body POST (jenis, format), segmen Indonesia maupun lama (SEGMEN);
     * akar = readme (cmd `artefak` / alias `readme`) atau manifest (alias lama cmd `manifest`). @return array{0: string, 1: bool} */
    public static function argumen(array $vars): array
    {
        $token = [];
        foreach (['scroll', 'id', 'jenis', 'format'] as $k) {
            $v = $vars[$k] ?? null;
            if (is_string($v) && $v !== '' && isset(self::SEGMEN[strtolower($v)])) {
                $token[] = self::SEGMEN[strtolower($v)];
            }
        }
        $jenis = strtolower((string) ($vars['cmd'] ?? self::CMD)) === 'manifest' ? 'manifest' : 'readme';
        foreach ($token as $t) {
            if (in_array($t, self::JENIS, true)) {
                $jenis = $t;
                break;
            }
        }

        return [$jenis, in_array('raw', $token, true)];
    }

    /** @return array<string, string> */
    public static function url(string $app, string $mvc): array
    {
        $b = "/{$app}/{$mvc}/" . self::CMD;

        // kunci tetap bahasa Inggris (= jenis internal, kontrak JSON phpvb-run); nilainya segmen v5.1.9
        return ['readme' => $b, 'manifest' => "{$b}/manifes", 'release' => "{$b}/rilis", 'spec' => "{$b}/spek", 'raw' => "{$b}/berkas",
            'indeks_app' => "/{$app}/" . self::CMD];
    }

    public static function baca(string $path): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents($path));
    }

    /** Sajikan dokumen MVC; balasan array (JSON) atau array kecil saat halaman (konten didaftarkan ke $doc). */
    public static function sajikan(object $controller, array $vars): mixed
    {
        global $pageID, $request;

        [$dirApp, $mvc] = self::lokasi($controller);
        $app = (string) ($pageID ?: basename($dirApp));
        [$jenis, $mentah] = self::argumen($vars);
        $url = self::url($app, $mvc);
        $p = self::path($dirApp, $mvc, $jenis);
        if ($p === null) {
            $rel = "apps/{$app}/view/{$mvc}/" . self::BERKAS[$jenis][0];
            $pesan = $jenis === 'spec'
                ? "{$rel} belum ada — tulis prompt spesifikasi MVC ini dengan phpvb_manifest (gov3phpvb) app={$app} mvc={$mvc} spec=…"
                : "{$rel} belum ada — bangkitkan dengan phpvb_manifest (gov3phpvb) app={$app} mvc={$mvc}";
            if (($request ?? '') === 'page' && !$mentah) {
                return self::halaman(self::judul($jenis, $app, $mvc, ''), '<div class="notification is-warning"><p>' . htmlspecialchars($pesan, ENT_QUOTES) . '</p></div>');
            }

            return self::tolak(404, 'ManifestNotFound', $pesan, ['app' => $app, 'mvc' => $mvc, 'jenis' => $jenis, 'url' => $url]);
        }
        $md = self::baca($p);
        if ($mentah) {
            return self::mentah($md, "{$app}-{$mvc}-{$jenis}.md");
        }
        $relPath = self::path($dirApp, $mvc, 'release');
        $release = $relPath ? self::baca($relPath) : '';
        $u = $jenis === 'manifest' ? Parser::urai($md) : null;
        $versi = $u ? ($u['versi'] !== '' ? $u['versi'] : Parser::versiRelease($release)) : Parser::versiRelease($release);
        $judul = self::judul($jenis, $app, $mvc, $versi);
        if (($request ?? '') === 'page') {
            // satu judul saja: H1 dokumen diangkat menjadi pageTitle tata letak, tidak dirender ulang di isi
            [$h1, $isi] = Parser::pisahJudul($jenis === 'manifest' ? Parser::tanpaBlokData($md) : $md);

            return self::halaman($h1 !== '' ? $h1 : $judul, markdown::render($isi));
        }
        $out = ['ok' => true, 'app' => $app, 'mvc' => $mvc, 'jenis' => $jenis, 'versi' => $versi, 'skema' => Parser::SKEMA,
            'berkas' => self::berkas($dirApp, $mvc), 'url' => $url, 'diubah' => ($t = @filemtime($p)) ? date('c', $t) : null];
        if ($u) {
            $blok = $u['mvc'][$mvc] ?? (count($u['mvc']) === 1 ? reset($u['mvc']) : null);
            $out['judul'] = $u['judul'];
            $out['app_data'] = $u['app'];
            $out['data'] = $blok['data'] ?? null;
            $out['galat'] = $u['galat'];
            if ($blok === null) {
                $out['galat'][] = "blok <!-- manifest:mvc:{$mvc} --> tidak ada di berkas ini";
            }
        }
        $out['markdown'] = $md;

        return $out;
    }

    private static function judul(string $jenis, string $app, string $mvc, string $versi): string
    {
        $v = $versi !== '' ? " v{$versi}" : '';

        return match ($jenis) {
            'readme' => "Artefak — {$app}/{$mvc}",
            'release' => "Riwayat rilis — {$app}/{$mvc}{$v}",
            'spec' => "Spec — {$app}/{$mvc}",
            default => "Manifest API — {$app}/{$mvc}{$v}",
        };
    }

    /** Halaman tanpa tombol: judul + isi markdown; template inti core/template/general/manifest.html. */
    private static function halaman(string $judul, string $html): array
    {
        global $doc;

        $doc->body('pageTitle', $judul);
        $doc->body('manifestHtml', $html);
        $doc->counter();
        if (!isset($doc->content) || !is_array($doc->content)) {
            $doc->content = [];
        }
        $doc->content[$doc->counter] = 'manifest.html';

        return ['ok' => true];
    }

    private static function mentah(string $isi, string $nama): mixed
    {
        if (self::$kirimMentah) {
            return (self::$kirimMentah)($isi, $nama);
        }
        if (!headers_sent()) {
            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: inline; filename="' . $nama . '"');
            header('X-Phpvb-Manifest-Skema: ' . Parser::SKEMA);
        }
        echo $isi;
        exit;
    }

    private static function tolak(int $status, string $code, string $msg, array $extra = []): array
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        return ['ok' => false, 'error' => $code, 'message' => $msg, 'class' => 'is-danger', 'notification' => "{$code}: {$msg}"] + $extra;
    }
}
