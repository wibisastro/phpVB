<?php namespace App\home\model;

use DB;
use Gov2lib\context;

/**
 * Dashboard portal landing (app home) — Lebah #5210.
 *
 * Menyajikan 2 section ringkas:
 *   1. Info Diri  — Wilayah / Instansi / Member (live count dari DB portal sendiri).
 *   2. Agregat    — rollup wilayah/instansi/member anak (hanya scope pusat + prov),
 *                   dibaca dari gov3_central.dashboard_rollup.
 *
 * Section "Pipeline Data Ingest" (SAKIP) SENGAJA tidak dibawa ke sini — itu
 * tinggal di app ingest (/ingest). Lihat README contoh/dashboard sakipai.
 *
 * extends crudHandler supaya konek DB sejak constructor (model document tidak
 * konek DB saat index()/render — lihat #5209).
 *
 * Konteks konten (rencana/filter-konteks-instansi-wilayah.md §3.6): saat filter
 * Instansi/Wilayah menunjuk portal LAIN, pembacaan tabel portal (dashboard_ingest,
 * member, instansi, wilayah) pindah ke koneksi reader lintas-DB lewat
 * \Gov2lib\context::connector(). Tanpa konteks (632 portal lain) reader() jatuh ke
 * DB:: global yang sama persis seperti sebelumnya — no-op.
 */
class dashboard extends \Gov2lib\crudHandler {
    function __construct () {
        global $config;
        $this->templateDir=__DIR__."/../view";
        $path=explode("\\",__CLASS__);
        $this->className=$path[sizeof($path)-1];
        $this->controller=__DIR__."/../".$this->className.".php";
        parent::__construct(trim($config->domain->attr['dsn']));
    }

    function dependencies () {
    }

    /**
     * Koneksi baca tabel PORTAL. Konteks menunjuk portal lain → MeekroDB dari
     * DSN reader lintas-DB (SELECT-only, DB di-override ke {scope}_{slug});
     * selain itu instance global DB:: — identik dengan DB::queryFirstRow(...) dsb,
     * karena DB::__callStatic memang meneruskan ke DB::getMDB().
     *
     * Dipanggil hanya dari dalam blok try: DSN reader yang belum terdaftar
     * membuat DBConnector::$db tidak terinisialisasi (\Error), dan itu ikut
     * tertangkap catch (\Throwable) di pemanggil → nilai default tetap dipakai.
     */
    private function reader(): \MeekroDB {
        if (context::isForeign()) {
            return context::connector()->db;
        }
        return DB::getMDB();
    }

    public function getKelengkapan(): ?array {
        try {
            // Cache lokal: scope metadata. self_* di tabel ini stale/abandoned —
            // di-override oleh live overlay. rollup_* DEPRECATED di sini —
            // sekarang dibaca dari gov3_central.dashboard_rollup.
            $row = $this->reader()->queryFirstRow("SELECT * FROM `dashboard_ingest` LIMIT 1");
            if (!$row) return null;

            // Live overlay self_* dari tabel lokal (member/instansi/wilayah).
            $row = $this->overlaySelfLive($row);

            // Centralized rollup_* untuk non-leaf (pusat + prov).
            if (in_array($row['scope_level'], ['pusat', 'prov'], true)) {
                $row = $this->overlayRollupCentral($row);
            }

            // rollup_* breakdown JSON tetap kalau ada (saat ini schema rollup tidak JSON, jadi no-op).
            foreach (['self_wilayah_breakdown', 'self_instansi_breakdown'] as $jf) {
                if (!empty($row[$jf]) && is_string($row[$jf])) {
                    $row[$jf] = json_decode($row[$jf], true);
                }
            }
            return $row;
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Baca rollup_* dari tabel central gov3_central.dashboard_rollup.
     * Overlay ke $row. Degrades gracefully kalau central table belum ada
     * atau user tidak punya SELECT (rollup_* stays default NULL).
     *
     * Sengaja TETAP lewat DB:: global walau konteks aktif: gov3_central sudah
     * lintas-DB dari kredensial portal sendiri, dan $row['scope_slug'] yang
     * dipakai sebagai kunci sudah berasal dari dashboard_ingest portal konteks.
     */
    private function overlayRollupCentral(array $row): array {
        try {
            $rollup = DB::queryFirstRow(
                "SELECT * FROM `gov3_central`.`dashboard_rollup` WHERE scope_slug=%s",
                $row['scope_slug']
            );
            if ($rollup) {
                foreach ($rollup as $k => $v) {
                    if (str_starts_with($k, 'rollup_') || $k === 'refresh_duration_ms') {
                        $row[$k] = $v;
                    }
                }
                $row['refresh_source'] = $rollup['refresh_source'] ?? $row['refresh_source'] ?? null;
            }
        } catch (\Throwable $e) { /* central tabel absent / no perm → keep NULL */ }
        return $row;
    }

    /**
     * Hitung self_* live dari tabel portal (portal sendiri, atau portal konteks
     * bila filter Instansi/Wilayah aktif — lihat reader()). Override field self_*
     * di $row. Degrades gracefully kalau tabel sumber tidak ada (counter tetap 0/null).
     */
    private function overlaySelfLive(array $row): array {
        // Member: COUNT total + per role (enum: webmaster/admin/member/guest).
        try {
            $m = $this->reader()->queryFirstRow("
                SELECT COUNT(*)                    AS total,
                       SUM(role='webmaster')       AS webmaster,
                       SUM(role='admin')           AS admin,
                       SUM(role='member')          AS member,
                       SUM(role='guest')           AS guest
                FROM `member`
            ");
            if ($m) {
                $row['self_member_total']     = (int) $m['total'];
                $row['self_member_webmaster'] = (int) $m['webmaster'];
                $row['self_member_admin']     = (int) $m['admin'];
                $row['self_member_member']    = (int) $m['member'];
                $row['self_member_guest']     = (int) $m['guest'];
            }
        } catch (\Throwable $e) { /* member table absent → keep cache defaults */ }

        // Instansi: COUNT total + breakdown by level_label.
        try {
            $db = $this->reader();
            $row['self_instansi_total'] = (int) $db->queryFirstField("SELECT COUNT(*) FROM `instansi`");
            $bd = $db->query("SELECT IFNULL(level_label, 'unknown') AS k, COUNT(*) AS v FROM `instansi` GROUP BY level_label");
            $row['self_instansi_breakdown'] = $this->bdAssoc($bd);
        } catch (\Throwable $e) { /* instansi absent → keep cache defaults */ }

        // Wilayah: total = count level PRIMARY relative to scope (bukan grand total
        // semua hierarchy). Sufiks UI sudah scoped (pusat→provinsi, prov→kab/kota,
        // kab/kota→kecamatan), jadi angka harus align dengan sufiks itu.
        // Breakdown tetap full per level supaya detail tetap terlihat.
        try {
            $primaryLevel = match($row['scope_level'] ?? '') {
                'pusat' => 'provinsi',
                'prov'  => 'kabupaten',   // gov3 wilayah pakai 'kabupaten' untuk gabungan kab+kota
                default => 'kecamatan',   // kab/kota
            };
            $db = $this->reader();
            $row['self_wilayah_total'] = (int) $db->queryFirstField(
                "SELECT COUNT(*) FROM `wilayah` WHERE level_label = %s", $primaryLevel
            );
            $bd = $db->query("SELECT IFNULL(level_label, 'unknown') AS k, COUNT(*) AS v FROM `wilayah` GROUP BY level_label");
            $row['self_wilayah_breakdown'] = $this->bdAssoc($bd);
        } catch (\Throwable $e) { /* wilayah absent → keep cache defaults */ }

        // self_refreshed_at: timestamp NOW (live, bukan cron timestamp).
        $row['self_refreshed_at'] = date('Y-m-d H:i:s');

        return $row;
    }

    private function bdAssoc(array $rows): array {
        $out = [];
        foreach ($rows as $r) $out[$r['k']] = (int) $r['v'];
        return $out;
    }
}
