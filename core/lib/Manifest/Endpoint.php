<?php

declare(strict_types=1);

namespace Gov2lib\Manifest;

use Gov2lib\markdown;

/**
 * Manifest per MVC — cmd bawaan `manifest` pada SETIAP controller app (lewat dispatcher core/init/index.php bila
 * controller tidak mendefinisikan method `manifest` sendiri). Endpoint = rute standar MVC itu, jadi konstruktor
 * controller (authenticate, konteks) sudah berjalan: siapa yang boleh membuka MVC, boleh membaca manifest-nya.
 *
 *   GET /{app}/{mvc}/manifest               → view/{mvc}/manifest.md   (kontrak API MVC ini, untuk agen/MCP)
 *   GET /{app}/{mvc}/manifest/readme        → view/{mvc}/readme.md     (konteks domain MVC)
 *   GET /{app}/{mvc}/manifest/release       → view/{mvc}/release.md    (kronologis versi MVC)
 *   …/raw (mis. /manifest/raw, /manifest/readme/raw) → text/markdown mentah
 *   POST /{app}/{mvc} {"cmd":"manifest","jenis":"readme","format":"raw"} — setara
 *
 * Balasan mengikuti Accept: HTML (halaman, tanpa tombol — navigasi lewat endpoint), JSON (markdown + blok data terurai).
 * Rujukan: phpVB/rencana/phpvb-manifest-mcp-run.md, release_notes/v5.1.7.md.
 *
 * @since 2026-10-08 (v5.1.7)
 */
final class Endpoint
{
    public const CMD = 'manifest';
    public const JENIS = ['manifest', 'readme', 'release'];
    public const BERKAS = [
        'manifest' => ['manifest.md'],
        'readme' => ['readme.md', 'README.md'],
        'release' => ['release.md', 'CHANGELOG.md'],
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

    /** Jenis & format dari segmen rute (id, scroll) atau body POST (jenis, format). @return array{0: string, 1: bool} */
    public static function argumen(array $vars): array
    {
        $token = [];
        foreach (['scroll', 'id', 'jenis', 'format'] as $k) {
            $v = $vars[$k] ?? null;
            if (is_string($v) && $v !== '') {
                $token[] = strtolower($v);
            }
        }
        $jenis = 'manifest';
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

        return ['manifest' => $b, 'readme' => "{$b}/readme", 'release' => "{$b}/release", 'raw' => "{$b}/raw", 'indeks_app' => "/{$app}/manifest"];
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
            $pesan = "{$rel} belum ada — bangkitkan dengan phpvb_manifest (gov3phpvb) app={$app} mvc={$mvc}";
            if (($request ?? '') === 'page' && !$mentah) {
                return self::halaman("Manifest API — {$app}/{$mvc}", '<div class="notification is-warning"><p>' . htmlspecialchars($pesan, ENT_QUOTES) . '</p></div>');
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
        $judul = match ($jenis) {
            'readme' => "README — {$app}/{$mvc}",
            'release' => "Riwayat rilis — {$app}/{$mvc}" . ($versi !== '' ? " v{$versi}" : ''),
            default => "Manifest API — {$app}/{$mvc}" . ($versi !== '' ? " v{$versi}" : ''),
        };
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
