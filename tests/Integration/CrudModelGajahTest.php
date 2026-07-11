<?php

declare(strict_types=1);

namespace Tests\Integration;

use Gov2lib\Database\SupabaseAdapter;
use Gov2lib\Exceptions\SupabaseException;
use Gov2lib\Exceptions\UnsupportedDriverOperationException;

/**
 * Integration test: crudModel di atas gajah nyata via driver supabase —
 * SKENARIO YANG SAMA dengan CrudModelMySQLTest (warisan CrudModelParityBase),
 * membuktikan keputusan T4 #6085 "ganti driver saja": model tanpa override
 * apa pun berperilaku identik di meekro (SQL) dan supabase (PostgREST).
 *
 * Tabel: phpvb_pilot_todo (flat) + phpvb_pilot_wilayah (hierarkis,
 * migration 20260711022755) — dua-duanya RLS anon read-only.
 *
 * Butuh env (otomatis SKIP bila tidak ada — tidak pernah dikomit):
 *   GAJAH_URL         base URL Supabase (default https://gajah.gov3.id)
 *   GAJAH_ANON_KEY    anon key instance gajah
 *   GAJAH_JWT_SECRET  JWT secret instance — service key di-mint in-memory
 *   (alternatif: GAJAH_SERVICE_KEY langsung, menggantikan mint)
 */
class CrudModelGajahTest extends CrudModelParityBase
{
    private static string $url = '';
    private static string $anonKey = '';
    private static string $serviceKey = '';

    private static string $dsnFile = __DIR__ . '/../../apps/gov2gajah/xml/dsnSource.test.xml';

    public static function setUpBeforeClass(): void
    {
        self::$url = getenv('GAJAH_URL') ?: 'https://gajah.gov3.id';
        self::$anonKey = getenv('GAJAH_ANON_KEY') ?: '';
        self::$serviceKey = getenv('GAJAH_SERVICE_KEY') ?: '';

        if (self::$serviceKey === '' && ($secret = getenv('GAJAH_JWT_SECRET')) && self::$anonKey !== '') {
            // Kong memvalidasi apikey sebagai string statis — token harus
            // byte-identik dengan SERVICE_ROLE_KEY instance: susun ulang
            // dari segmen header anon verbatim + claim role diganti
            // (HS256 deterministik). Detail: SupabaseGajahTest (T3).
            $segments = explode('.', self::$anonKey);

            if (count($segments) !== 3) {
                self::markTestSkipped('GAJAH_ANON_KEY bukan JWT — tidak bisa mint service key');
            }

            [$header, $anonPayload] = $segments;
            $claims = json_decode(base64_decode(strtr($anonPayload, '-_', '+/')), true) ?: [];
            $claims['role'] = 'service_role';

            $payload = rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=');
            $signature = rtrim(strtr(base64_encode(
                hash_hmac('sha256', "{$header}.{$payload}", $secret, true)
            ), '+/', '-_'), '=');

            self::$serviceKey = "{$header}.{$payload}.{$signature}";
        }

        if (self::$anonKey === '' || self::$serviceKey === '') {
            self::markTestSkipped('env GAJAH_ANON_KEY + GAJAH_JWT_SECRET/GAJAH_SERVICE_KEY tidak di-set');
        }

        try {
            self::service()->restCount('phpvb_pilot_wilayah');
        } catch (SupabaseException $e) {
            self::markTestSkipped('gajah tidak terjangkau / tabel parity belum ada: ' . $e->getMessage());
        }

        // Fixture DSN app gov2gajah (anon key saja — service key TIDAK
        // pernah ditulis ke disk; dipakai via credentialDB in-memory)
        $url = self::$url;
        $key = self::$anonKey;
        file_put_contents(self::$dsnFile, <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <list>
              <dsn>
                <name>master</name>
                <driver>supabase</driver>
                <url>{$url}</url>
                <key>{$key}</key>
              </dsn>
            </list>
            XML);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$dsnFile);
    }

    private static function service(): SupabaseAdapter
    {
        return new SupabaseAdapter(['url' => self::$url, 'key' => self::$serviceKey]);
    }

    protected function tearDown(): void
    {
        $db = self::service();

        foreach ($this->created as [$table, $id]) {
            try {
                $db->restDelete($table, ['id' => $id]);
            } catch (SupabaseException) {
                // baris sudah dihapus oleh test-nya sendiri
            }
        }

        $this->created = [];
    }

    protected function pageId(): string
    {
        return 'gov2gajah';
    }

    protected function flatTable(): string
    {
        return 'phpvb_pilot_todo';
    }

    protected function hierTable(): string
    {
        return 'phpvb_pilot_wilayah';
    }

    protected function model(string $table): object
    {
        $model = $this->newHarnessModel($table);

        // Tulis butuh service_role (RLS) — ganti DSN via XML in-memory,
        // tanpa menulis service key ke disk
        $url = self::$url;
        $key = self::$serviceKey;
        $xml = new \SimpleXMLElement(<<<XML
            <list>
              <dsn>
                <name>svc</name>
                <driver>supabase</driver>
                <url>{$url}</url>
                <key>{$key}</key>
              </dsn>
            </list>
            XML);
        $model->credentialDB($xml, 'svc');

        return $model;
    }

    public function testModelPakaiAdapterSupabaseTanpaOverride(): void
    {
        $model = $this->model($this->flatTable());

        $this->assertEquals('supabase', $model->driverName());
        $this->assertInstanceOf(SupabaseAdapter::class, $model->db());
    }

    public function testJoinHeavyDitolakDenganPesanJelas(): void
    {
        $model = $this->model($this->hierTable());

        $this->expectException(UnsupportedDriverOperationException::class);
        $this->expectExceptionMessageMatches('/doBrowseTags.*RPC\/view/s');

        $model->doBrowseTags(1, 'wilayah', 'berita');
    }

    public function testDoTaggingDitolakDenganPesanJelas(): void
    {
        $model = $this->model($this->hierTable());

        $this->expectException(UnsupportedDriverOperationException::class);

        $model->doTagging(['source_id' => 1, 'target_id' => 2], 'wilayah', 'berita', '', 'judul');
    }
}
