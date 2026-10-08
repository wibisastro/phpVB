<?php

namespace Gov2lib;

/**
 * Model endpoint manifest (`/{app}/manifest`, controller Gov2lib\manifest): hanya membaca tiga
 * dokumen bawaan app — tidak menyentuh DB app.
 *
 *   manifest → apps/{app}/view/manifest.md   (kontrak API, dibaca agen; wajib ada agar endpoint hidup)
 *   release  → apps/{app}/view/release.md    (kronologis versi)
 *   readme   → apps/{app}/README.md          (konteks domain; cadangan readme.md / view/readme.md)
 *
 * DSN domain dihubungkan seperti roleHandler agar authenticate('guest') bisa membaca member
 * bila cookie belum memuat `id`.
 *
 * @since 2026-10-08 (v5.1.5)
 */
class manifestHandler extends checkExist
{
    public const JENIS = ['manifest', 'release', 'readme'];

    /** Kandidat path relatif akar app per jenis — yang pertama ada dipakai. */
    public const PATH = [
        'manifest' => ['view/manifest.md'],
        'release' => ['view/release.md', 'release.md', 'CHANGELOG.md'],
        'readme' => ['README.md', 'readme.md', 'view/readme.md'],
    ];

    public function __construct()
    {
        global $vars, $config, $pageID;

        parent::__construct(trim((string) ($config->domain->attr['dsn'] ?? '')));
        $_app = $this->checkAppDir((string) ($vars['app'] ?? $pageID ?? ''));
        $this->templateDir = __DIR__ . '/../../apps/' . $_app . '/view';
        $this->controller = __DIR__ . '/manifest.php';
    }

    /** Akar app (dipisah agar uji bisa menunjuk folder sementara). */
    public function dirApp(): string
    {
        global $pageID;

        return __DIR__ . "/../../apps/{$pageID}";
    }

    /** Path berkas jenis tertentu yang ADA; null bila tidak ada. */
    public function path(string $jenis): ?string
    {
        foreach (self::PATH[$jenis] ?? [] as $rel) {
            $p = $this->dirApp() . '/' . $rel;
            if (is_file($p) && is_readable($p)) {
                return $p;
            }
        }

        return null;
    }

    public function ada(string $jenis): bool
    {
        return $this->path($jenis) !== null;
    }

    /** Isi berkas (LF); '' bila tidak ada. */
    public function baca(string $jenis): string
    {
        $p = $this->path($jenis);
        if ($p === null) {
            return '';
        }

        return str_replace("\r\n", "\n", (string) file_get_contents($p));
    }

    /** @return array<string, bool> */
    public function berkas(): array
    {
        $out = [];
        foreach (self::JENIS as $j) {
            $out[$j] = $this->ada($j);
        }

        return $out;
    }

    /** Waktu ubah terakhir (ISO 8601) atau null. */
    public function diubah(string $jenis): ?string
    {
        $p = $this->path($jenis);
        if ($p === null) {
            return null;
        }
        $t = @filemtime($p);

        return $t ? date('c', $t) : null;
    }
}
