<?php

declare(strict_types=1);

namespace Gov2lib\Manifest;

/**
 * Pengurai `apps/{app}/view/manifest.md` — logika murni, tanpa I/O, tanpa global.
 *
 * Bentuk berkas (skema 1; penulisnya generator gov3phpvb `manifest.py`, pembacanya controller
 * Gov2lib\manifest dan MCP phpvb-run — ketiganya memakai fixture uji yang sama):
 *
 *   # Manifest API — {app} v{versi}
 *   <!-- manifest:app start -->
 *   ```json manifest-app
 *   {"skema":1,"app":"bukutamu","versi":"1.2.0","transport":{...}}
 *   ```
 *   <!-- manifest:app end -->
 *   … teks bebas (konvensi transport, tabel MVC) …
 *   <!-- manifest:mvc:kunjungan start -->
 *   ### kunjungan — Kunjungan Tamu
 *   … tabel cmd untuk pembaca manusia …
 *   ```json manifest
 *   {"mvc":"kunjungan","cmd":{"add":{"method":"POST","uri":"/bukutamu/kunjungan"}}}
 *   ```
 *   <!-- manifest:mvc:kunjungan end -->
 *
 * Blok bertanda milik generator (diganti utuh saat regenerasi); teks di luar blok milik manusia.
 * Komentar HTML hilang saat dirender (markdown::render memakai html_input=strip) sehingga tanda
 * tidak tampak di halaman.
 *
 * @since 2026-10-08 (v5.1.5) — rencana phpVB/rencana/phpvb-manifest-mcp-run.md
 */
final class Parser
{
    public const SKEMA = 1;
    public const FENCE_APP = 'json manifest-app';
    public const FENCE_MVC = 'json manifest';
    public const RE_NAMA = '/^[a-z][a-z0-9_]{0,63}$/';

    /**
     * Urai seluruh berkas.
     *
     * @return array{judul: string, versi: string, app: array<string, mixed>, mvc: array<string, array{md: string, data: array<string, mixed>|null}>, galat: list<string>}
     */
    public static function urai(string $md): array
    {
        $md = str_replace("\r\n", "\n", $md);
        $galat = [];

        $judul = '';
        if (preg_match('/^#\s+(.+?)\s*$/m', $md, $m)) {
            $judul = trim($m[1]);
        }

        $app = [];
        $blokApp = self::blok($md, 'app');
        if ($blokApp !== null) {
            $json = self::fence($blokApp, self::FENCE_APP);
            if ($json === null) {
                $galat[] = 'blok app: tidak ada ```' . self::FENCE_APP . '```';
            } else {
                $d = self::decode($json, 'blok app', $galat);
                $app = is_array($d) ? $d : [];
            }
        }

        $mvc = [];
        if (preg_match_all('/<!--\s*manifest:mvc:([A-Za-z0-9_]+)\s+start\s*-->/', $md, $mm)) {
            foreach ($mm[1] as $nama) {
                $isi = self::blok($md, "mvc:{$nama}");
                if ($isi === null) {
                    $galat[] = "MVC {$nama}: tanda end tidak ditemukan";
                    continue;
                }
                if (!preg_match(self::RE_NAMA, $nama)) {
                    $galat[] = "MVC {$nama}: nama tidak valid (huruf kecil/angka/_)";
                }
                $json = self::fence($isi, self::FENCE_MVC);
                $data = null;
                if ($json === null) {
                    $galat[] = "MVC {$nama}: blok data ```" . self::FENCE_MVC . '``` tidak ada';
                } else {
                    $d = self::decode($json, "MVC {$nama}", $galat);
                    if (is_array($d)) {
                        $data = $d;
                        foreach (self::periksaMvc($nama, $d) as $g) {
                            $galat[] = $g;
                        }
                    }
                }
                $mvc[$nama] = ['md' => trim($isi), 'data' => $data];
            }
        }

        $versi = self::versi($md, $app, $judul);

        return ['judul' => $judul, 'versi' => $versi, 'app' => $app, 'mvc' => $mvc, 'galat' => $galat];
    }

    /** Isi di antara tanda start/end (tanpa tandanya); null bila tidak lengkap. */
    public static function blok(string $md, string $kunci): ?string
    {
        $k = preg_quote($kunci, '/');
        if (!preg_match("/<!--\\s*manifest:{$k}\\s+start\\s*-->\\n?(.*?)\\n?<!--\\s*manifest:{$k}\\s+end\\s*-->/s", $md, $m)) {
            return null;
        }

        return $m[1];
    }

    /** Isi fence ```<info>\n…\n``` pertama di dalam teks; null bila tidak ada. */
    public static function fence(string $teks, string $info): ?string
    {
        $i = preg_quote($info, '/');
        if (!preg_match("/^```{$i}[ \\t]*\\n(.*?)\\n```[ \\t]*$/ms", $teks, $m)) {
            return null;
        }

        return $m[1];
    }

    /** Markdown tanpa fence data (untuk halaman HTML — data tersedia lewat JSON/raw). */
    public static function tanpaBlokData(string $md): string
    {
        $md = str_replace("\r\n", "\n", $md);
        $a = preg_quote(self::FENCE_APP, '/');
        $m = preg_quote(self::FENCE_MVC, '/');
        $md = (string) preg_replace("/^```(?:{$a}|{$m})[ \\t]*\\n.*?\\n```[ \\t]*$\\n?/ms", '', $md);

        return $md;
    }

    /**
     * Pisahkan judul `# …` pertama dari isi markdown — judul dipakai sebagai pageTitle tata letak supaya tidak tampil dua kali.
     *
     * @return array{0: string, 1: string} [judul ('' bila baris pertama bukan H1), isi tanpa baris judul]
     */
    public static function pisahJudul(string $md): array
    {
        $md = str_replace("\r\n", "\n", $md);
        if (preg_match('/\A\s*#[ \t]+(.+?)[ \t]*(?:\n|\z)/', $md, $m)) {
            return [trim($m[1]), ltrim(substr($md, strlen($m[0])), "\n")];
        }

        return ['', $md];
    }

    /** Versi app: blok app → judul `vX.Y.Z` → entri teratas release.md → ''. */
    public static function versi(string $md, array $app = [], string $judul = '', string $release = ''): string
    {
        $v = trim((string) ($app['versi'] ?? ''));
        if ($v !== '') {
            return $v;
        }
        if ($judul === '' && preg_match('/^#\s+(.+?)\s*$/m', $md, $m)) {
            $judul = $m[1];
        }
        if (preg_match('/\bv(\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.]+)?)\b/', $judul, $m)) {
            return $m[1];
        }

        return self::versiRelease($release);
    }

    /** Versi teratas di release.md: `## v1.2.0 — 2026-10-08` (atau `## 1.2.0`). */
    public static function versiRelease(string $release): string
    {
        if ($release !== '' && preg_match('/^##\s+v?(\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.]+)?)\b/m', $release, $m)) {
            return $m[1];
        }

        return '';
    }

    /** Ringkasan per MVC untuk indeks JSON (tanpa md). */
    public static function ringkas(array $urai): array
    {
        $out = [];
        foreach ($urai['mvc'] as $nama => $b) {
            $d = $b['data'] ?? [];
            $out[$nama] = [
                'judul' => (string) ($d['judul'] ?? $nama),
                'pola' => array_values((array) ($d['pola'] ?? [])),
                'tabel' => (string) ($d['tabel'] ?? ''),
                'auth' => $d['auth'] ?? null,
                'cmd' => array_keys((array) ($d['cmd'] ?? [])),
                'alur' => array_keys((array) ($d['alur'] ?? [])),
                'perlu_dilengkapi' => !empty($d['perlu_dilengkapi']),
                'data' => $d === [] ? null : true,
            ];
        }

        return $out;
    }

    /** @return list<string> */
    private static function periksaMvc(string $nama, array $d): array
    {
        $g = [];
        $disebut = (string) ($d['mvc'] ?? '');
        if ($disebut !== '' && $disebut !== $nama) {
            $g[] = "MVC {$nama}: blok data menyebut mvc '{$disebut}'";
        }
        $cmd = $d['cmd'] ?? null;
        if (!is_array($cmd) || $cmd === []) {
            $g[] = "MVC {$nama}: tanpa cmd";

            return $g;
        }
        foreach ($cmd as $c => $spek) {
            if (!is_array($spek)) {
                $g[] = "MVC {$nama}: cmd {$c} bukan objek";
                continue;
            }
            $method = strtoupper((string) ($spek['method'] ?? ''));
            if (!in_array($method, ['GET', 'POST'], true)) {
                $g[] = "MVC {$nama}: cmd {$c} method harus GET/POST";
            }
            if (trim((string) ($spek['uri'] ?? '')) === '') {
                $g[] = "MVC {$nama}: cmd {$c} tanpa uri";
            }
        }

        return $g;
    }

    private static function decode(string $json, string $label, array &$galat): mixed
    {
        $d = json_decode($json, true);
        if (!is_array($d)) {
            $galat[] = "{$label}: JSON tidak valid (" . json_last_error_msg() . ')';

            return null;
        }

        return $d;
    }
}
