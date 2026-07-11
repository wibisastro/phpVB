<?php

declare(strict_types=1);

namespace Tests\Integration;

use Gov2lib\Database\SupabaseAdapter;
use Gov2lib\Database\SupabaseCrudRepository;
use Gov2lib\Exceptions\SupabaseException;
use PHPUnit\Framework\TestCase;

/**
 * Integration test: SupabaseAdapter di atas gajah (Supabase self-hosted) nyata.
 * Fase T3 #6085 — pilot tabel public.phpvb_gajah_todo dengan RLS:
 * anon read-only, service_role penuh (migration 20260710173603).
 *
 * Butuh env (otomatis SKIP bila tidak ada — tidak pernah dikomit):
 *   GAJAH_URL         base URL Supabase (default https://gajah.gov3.id)
 *   GAJAH_ANON_KEY    anon key instance gajah
 *   GAJAH_JWT_SECRET  JWT secret instance — token service_role di-mint
 *                     in-memory saat setup, tidak pernah ditulis ke disk
 *   (alternatif: GAJAH_SERVICE_KEY langsung, menggantikan mint)
 */
class SupabaseGajahTest extends TestCase
{
    private const TABLE = 'phpvb_gajah_todo';

    private static string $url = '';
    private static string $anonKey = '';
    private static string $serviceKey = '';

    /** @var int[] id baris yang dibuat test — dibersihkan di tearDown */
    private array $created = [];

    public static function setUpBeforeClass(): void
    {
        self::$url = getenv('GAJAH_URL') ?: 'https://gajah.gov3.id';
        self::$anonKey = getenv('GAJAH_ANON_KEY') ?: '';
        self::$serviceKey = getenv('GAJAH_SERVICE_KEY') ?: '';

        if (self::$serviceKey === '' && ($secret = getenv('GAJAH_JWT_SECRET')) && self::$anonKey !== '') {
            // Kong memvalidasi apikey terhadap daftar key statis, bukan
            // verifikasi JWT — token harus byte-identik dengan
            // SERVICE_ROLE_KEY instance. Generator Supabase memakai
            // header dan iat/exp yang sama dengan anon key, jadi susun
            // ulang token dengan segmen header anon verbatim + claim role
            // diganti → byte yang persis sama (HS256 deterministik).
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
            self::anon()->restCount(self::TABLE);
        } catch (SupabaseException $e) {
            self::markTestSkipped('gajah tidak terjangkau: ' . $e->getMessage());
        }
    }

    private static function anon(): SupabaseAdapter
    {
        return new SupabaseAdapter(['url' => self::$url, 'key' => self::$anonKey]);
    }

    private static function service(): SupabaseAdapter
    {
        return new SupabaseAdapter(['url' => self::$url, 'key' => self::$serviceKey]);
    }

    protected function tearDown(): void
    {
        $db = self::service();

        foreach ($this->created as $id) {
            try {
                $db->restDelete(self::TABLE, ['id' => $id]);
            } catch (SupabaseException) {
                // baris sudah dihapus oleh test-nya sendiri
            }
        }

        $this->created = [];
    }

    public function testServiceRoleCrudRoundtrip(): void
    {
        $repo = new SupabaseCrudRepository(self::service());

        $id = $repo->add(self::TABLE, ['nama' => 'Tugas T3', 'status' => 'baru', 'created_by' => 7]);
        $this->assertNotNull($id);
        $this->created[] = $id;

        $row = $repo->read(self::TABLE, $id);
        $this->assertEquals('Tugas T3', $row['nama']);
        $this->assertEquals(7, $row['created_by']);
        $this->assertNotEmpty($row['created_at']);

        $this->assertSame(1, $repo->update(self::TABLE, ['status' => 'selesai'], $id));
        $this->assertEquals('selesai', $repo->read(self::TABLE, $id)['status']);

        $this->assertGreaterThanOrEqual(1, $repo->count(self::TABLE, ['status' => 'selesai']));

        $rows = $repo->browse(self::TABLE, ['status' => 'selesai'], limit: 10, orderBy: 'id DESC');
        $this->assertEquals($id, $rows[0]['id']);

        $this->assertSame(1, $repo->delete(self::TABLE, $id));
        $this->assertNull($repo->read(self::TABLE, $id));
        $this->created = [];
    }

    public function testAnonBisaBaca(): void
    {
        $repo = new SupabaseCrudRepository(self::anon());

        $this->assertIsInt($repo->count(self::TABLE));
        $this->assertIsArray($repo->browse(self::TABLE, limit: 5));
    }

    public function testAnonDitolakMenulisKarenaRls(): void
    {
        $repo = new SupabaseCrudRepository(self::anon());

        try {
            $id = $repo->add(self::TABLE, ['nama' => 'Harus ditolak RLS']);

            if ($id !== null) {
                $this->created[] = $id;
            }

            $this->fail('Insert dengan anon key seharusnya ditolak RLS');
        } catch (SupabaseException $e) {
            // 42501 = insufficient_privilege (RLS/grant menolak)
            $this->assertSame('42501', $e->pgCode, $e->getMessage());
        }
    }

    public function testColumnListDariOpenApi(): void
    {
        $columns = self::service()->columnList(self::TABLE);

        foreach (['id', 'nama', 'status', 'created_by', 'created_at'] as $expected) {
            $this->assertArrayHasKey($expected, $columns);
        }
    }

    public function testCrudModelPathInsertUpdateViaDbAdapter(): void
    {
        // Jalur crudModel T2 yang sudah dirouting ke db(): doAdd/doUpdate
        // tabel flat hanya butuh columnList + insert + update("id=%i") —
        // semuanya terpetakan ke PostgREST tanpa SQL.
        $db = self::service();

        $id = $db->insert(self::TABLE, ['nama' => 'Via db()', 'status' => 'baru']);
        $this->assertGreaterThan(0, $id);
        $this->created[] = $id;

        $affected = $db->update(self::TABLE, ['status' => 'proses'], 'id=%i', $id);
        $this->assertSame(1, $affected);

        $this->assertSame(1, $db->count(self::TABLE, 'id=%i', $id));
    }
}
