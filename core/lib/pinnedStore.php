<?php

namespace Gov2lib;

/**
 * Penyimpanan pinned JSON — #6134 slice C.
 *
 * Kambing = system of record (path `portal-config/{dsn}/options/{app}.json`
 * di akun kambing instansi); portal membaca lewat cache lokal disposable
 * (lihat gov2option::pinnedPath) dengan TTL + revalidasi etag, pola
 * stale-while-revalidate: kambing down → cache lama; belum pernah ada →
 * fall-through tier berikutnya. Tanpa env kambing seluruh sync() = no-op,
 * cache lokal jadi satu-satunya sumber pinned (perilaku slice A).
 *
 * Sidecar `{app}.json.sync` (mesin): {etag, status ok|missing|error, checked_at}
 * — pembatas 1 request HTTP per app per jendela TTL, apa pun hasilnya.
 *
 * @package Gov2lib
 */
class pinnedStore
{
    /** Detik antar revalidasi ke kambing (override: env GOV2_PINNED_TTL) */
    public const TTL_DEFAULT = 300;

    private ?webdavClient $dav;

    public function __construct(?webdavClient $dav = null)
    {
        $this->dav = $dav ?? webdavClient::fromEnv();
    }

    /** Path di akun kambing instansi (konvensi #6134 note-3) */
    public static function remotePath(string $dsn, string $app): string
    {
        return "portal-config/{$dsn}/options/{$app}.json";
    }

    /**
     * Bangun envelope pinned dari rows DB satu MVC utuh: id dibuat sintetis
     * berurutan (selaras flattenOptionsXml), parent_id di-remap mengikuti;
     * provenance di meta. Murni — tanpa I/O.
     */
    public static function buildEnvelope(array $rows, string $app, ?int $savedBy = null, string $source = ''): array
    {
        $map = [0 => 0];
        $i = 0;

        foreach ($rows as $row) {
            $map[(int) ($row['id'] ?? 0)] = ++$i;
        }

        $out = [];
        $i = 0;

        foreach ($rows as $row) {
            $out[] = [
                'id' => ++$i,
                'parent_id' => $map[(int) ($row['parent_id'] ?? 0)] ?? 0,
                'app' => $app,
                'nama' => (string) ($row['nama'] ?? ''),
                'type' => (string) ($row['type'] ?? 'text'),
                'privilege' => (string) ($row['privilege'] ?? 'admin'),
                'status' => (string) ($row['status'] ?? 'on'),
                'value' => $row['value'] ?? null,
                'level' => (int) ($row['level'] ?? 2),
                'level_label' => (string) ($row['level_label'] ?? ''),
                'keterangan' => $row['keterangan'] ?? null,
            ];
        }

        return [
            'gov2options' => gov2option::PINNED_VERSION,
            'meta' => [
                'app' => $app,
                'saved_at' => date('c'),
                'saved_by' => $savedBy,
                'source' => $source,
            ],
            'rows' => $out,
        ];
    }

    /**
     * Save-to-lower-tier: envelope → PUT kambing (bila terkonfigurasi) →
     * refresh cache lokal. Remote gagal ≠ batal — cache lokal tetap ditulis
     * (portal langsung membacanya) dan status remote dilaporkan agar UI
     * bisa memperingatkan divergensi dari system of record.
     *
     * @return array{remote:string, cache:string}
     */
    public function save(string $dsn, string $app, array $envelope): array
    {
        $json = json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $result = ['remote' => 'skipped', 'cache' => 'failed'];
        $etag = null;

        if ($this->dav !== null) {
            $res = $this->dav->put(self::remotePath($dsn, $app), $json);
            $ok = $res['status'] >= 200 && $res['status'] < 300;
            $result['remote'] = $ok ? 'ok' : "failed:{$res['status']}";
            $etag = $ok ? $res['etag'] : null;

            if (!$ok) {
                error_log("pinnedStore: PUT kambing gagal ({$res['status']}) utk {$app} — cache lokal tetap ditulis");
            }
        }

        if ($this->writeCache($dsn, $app, $json, $etag)) {
            $result['cache'] = 'ok';
        }

        return $result;
    }

    /**
     * Revalidasi cache lokal terhadap kambing — dipanggil resolver sebelum
     * baca (gov2option::pinnedRows). No-op bila: kambing tidak dikonfigurasi,
     * path invalid, atau masih di dalam jendela TTL.
     */
    public function sync(string $dsn, string $app): void
    {
        if ($this->dav === null) {
            return;
        }

        $file = gov2option::pinnedPath($dsn, $app);

        if ($file === null) {
            return;
        }

        $meta = $this->readMeta($file);
        $envTtl = getenv('GOV2_PINNED_TTL');
        $ttl = is_numeric($envTtl) ? (int) $envTtl : self::TTL_DEFAULT; // '0' sah (selalu revalidasi)

        if ($meta !== null && (time() - (int) ($meta['checked_at'] ?? 0)) < $ttl) {
            return; // masih segar — pakai keadaan lokal apa adanya (termasuk negative cache 'missing')
        }

        $res = $this->dav->get(self::remotePath($dsn, $app), $meta['etag'] ?? null);

        if ($res['status'] === 304) {
            $this->writeMeta($file, ['etag' => $meta['etag'] ?? null, 'status' => 'ok']);
        } elseif ($res['status'] === 200 && $res['body'] !== null) {
            $this->atomicWrite($file, $res['body']);
            $this->writeMeta($file, ['etag' => $res['etag'], 'status' => 'ok']);
        } elseif ($res['status'] === 404) {
            @unlink($file); // pinned dicabut dari kambing → tier bawah kembali berlaku
            $this->writeMeta($file, ['etag' => null, 'status' => 'missing']);
        } else {
            // Jaringan/5xx: stale-while-revalidate — sajikan cache lama,
            // checked_at tetap dicatat supaya backoff satu jendela TTL
            $this->writeMeta($file, ['etag' => $meta['etag'] ?? null, 'status' => 'error']);
            error_log("pinnedStore: revalidasi kambing gagal (status {$res['status']}) utk {$app} — pakai cache lama");
        }
    }

    /**
     * Jalur cepat untuk resolver: sekali getenv per proses; tanpa env
     * kambing tidak ada objek/HTTP yang dibuat sama sekali.
     */
    public static function syncFromEnv(string $dsn, string $app): void
    {
        static $store = null;
        static $configured = null;

        $configured ??= (bool) getenv('GOV2_KAMBING_URL');

        if (!$configured) {
            return;
        }

        $store ??= new self();
        $store->sync($dsn, $app);
    }

    /** Tulis cache lokal (atomik) + sidecar sync meta */
    public function writeCache(string $dsn, string $app, string $json, ?string $etag = null): bool
    {
        $file = gov2option::pinnedPath($dsn, $app);

        if ($file === null || !$this->atomicWrite($file, $json)) {
            return false;
        }

        $this->writeMeta($file, ['etag' => $etag, 'status' => 'ok']);

        return true;
    }

    /** Tulis tmp + rename di direktori yang sama — pembaca tak pernah melihat file setengah jadi */
    private function atomicWrite(string $file, string $content): bool
    {
        $dir = dirname($file);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            error_log("pinnedStore: gagal membuat direktori cache: {$dir}");

            return false;
        }

        $tmp = $file . '.tmp.' . getmypid();

        if (@file_put_contents($tmp, $content) === false || !@rename($tmp, $file)) {
            @unlink($tmp);
            error_log("pinnedStore: gagal menulis cache: {$file}");

            return false;
        }

        return true;
    }

    private function readMeta(string $file): ?array
    {
        $raw = @file_get_contents($file . '.sync');
        $meta = $raw === false ? null : json_decode($raw, true);

        return is_array($meta) ? $meta : null;
    }

    private function writeMeta(string $file, array $meta): void
    {
        $meta['checked_at'] = time();
        $this->atomicWrite($file . '.sync', (string) json_encode($meta));
    }
}
