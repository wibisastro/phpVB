<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Database\ReadOnlySql as R;
use Gov2lib\Database\ReadOnlySqlException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests Gov2lib\Database\ReadOnlySql (v5.1.4) — logika murni endpoint agen DB baca-saja:
 * penjaga SQL, kolom waktu/kunci, SQL bantu, format nilai, penyamaran, catatan konvensi phpVB.
 * Tanpa koneksi DB; perilaku koneksi (READ ONLY, batas waktu) diuji hidup di server lewat gov3phpvb.
 */
class ReadOnlySqlTest extends TestCase
{
    /** Kolom tiruan information_schema: nama, data_type, kunci, ekstra */
    private const KOLOM_TAMU = [
        ['nama' => 'id', 'data_type' => 'int', 'kunci' => 'PRI', 'ekstra' => 'auto_increment'],
        ['nama' => 'nama', 'data_type' => 'varchar', 'kunci' => '', 'ekstra' => ''],
        ['nama' => 'instansi', 'data_type' => 'varchar', 'kunci' => '', 'ekstra' => ''],
        ['nama' => 'created_at', 'data_type' => 'datetime', 'kunci' => '', 'ekstra' => ''],
        ['nama' => 'created_by', 'data_type' => 'int', 'kunci' => '', 'ekstra' => ''],
        ['nama' => 'modify_at', 'data_type' => 'datetime', 'kunci' => '', 'ekstra' => ''],
        ['nama' => 'modify_by', 'data_type' => 'int', 'kunci' => '', 'ekstra' => ''],
    ];

    public static function sqlBacaDiterima(): array
    {
        return [
            ['SELECT * FROM tamu'],
            ["select id, nama from tamu where nama like '%budi%';"],
            ['  (SELECT 1) UNION (SELECT 2)'],
            ['WITH t AS (SELECT * FROM tamu) SELECT COUNT(*) FROM t'],
            ['SHOW CREATE TABLE tamu'],
            ['DESCRIBE tamu'],
            ['EXPLAIN SELECT * FROM tamu WHERE id = 1'],
            ["SELECT * FROM tamu WHERE keterangan = 'delete; drop table x'"],
            ['SELECT `update`, `delete` FROM log_aksi'],
            ['SELECT modify_at, update_time FROM tamu -- komentar; DELETE'],
            ["SELECT 'it''s' AS x"],
            ["SELECT 'a\\'b; DROP TABLE x' AS x"],
        ];
    }

    #[DataProvider('sqlBacaDiterima')]
    public function testSqlBacaDiterima(string $sql): void
    {
        $hasil = R::periksa($sql);
        $this->assertStringEndsNotWith(';', $hasil);
    }

    public static function sqlSelainBacaDitolak(): array
    {
        return [
            ['DELETE FROM tamu', 'BUKAN_BACA'],
            ["update tamu set nama='x'", 'BUKAN_BACA'],
            ['INSERT INTO tamu VALUES (1)', 'BUKAN_BACA'],
            ['DROP TABLE tamu', 'BUKAN_BACA'],
            ['SET GLOBAL read_only=0', 'BUKAN_BACA'],
            ['SET SESSION TRANSACTION READ WRITE', 'BUKAN_BACA'],
            ['SELECT 1; DELETE FROM tamu', 'BANYAK_PERNYATAAN'],
            ['SELECT 1 /* x */; SELECT 2', 'BANYAK_PERNYATAAN'],
            ['SELECT 1 --x; DELETE FROM tamu', 'BANYAK_PERNYATAAN'], // `--x` bukan komentar di MySQL
            ['WITH t AS (SELECT 1) DELETE FROM tamu', 'BUKAN_BACA'],
            ['EXPLAIN DELETE FROM tamu', 'BUKAN_BACA'],
            ["SELECT * FROM tamu INTO OUTFILE '/tmp/x'", 'SQL_TERLARANG'],
            ['SELECT * FROM tamu FOR UPDATE', 'SQL_TERLARANG'],
            ['SELECT SLEEP(100)', 'SQL_TERLARANG'],
            ["SELECT LOAD_FILE('/etc/passwd')", 'SQL_TERLARANG'],
            ['SELECT /*!50000 1 */', 'KOMENTAR_EKSEKUTABEL'],
            ["SELECT 'tak ditutup", 'SQL_TIDAK_LENGKAP'],
            ['', 'SQL_KOSONG'],
        ];
    }

    #[DataProvider('sqlSelainBacaDitolak')]
    public function testSqlSelainBacaDitolak(string $sql, string $kode): void
    {
        try {
            R::periksa($sql);
            $this->fail("harus ditolak: {$sql}");
        } catch (ReadOnlySqlException $e) {
            $this->assertSame($kode, $e->kode);
            $this->assertNotSame('', $e->pesan());
        }
    }

    public function testSejakRelatifDanAbsolut(): void
    {
        $this->assertSame('NOW() - INTERVAL 15 MINUTE', R::sejak('15m'));
        $this->assertSame('NOW() - INTERVAL 2 HOUR', R::sejak('2j'));
        $this->assertSame('NOW() - INTERVAL 2 HOUR', R::sejak('2 jam'));
        $this->assertSame('NOW() - INTERVAL 2 HOUR', R::sejak('2h'));
        $this->assertSame('NOW() - INTERVAL 1 DAY', R::sejak('1d'));
        $this->assertSame("'2026-10-08 09:30:00'", R::sejak('2026-10-08T09:30'));
        $this->assertSame("'2026-10-08 09:30:05'", R::sejak('2026-10-08 09:30:05'));
        $this->assertSame("'2026-10-08 00:00:00'", R::sejak('2026-10-08'));
        $this->expectException(ReadOnlySqlException::class);
        R::sejak("kemarin' OR 1=1 --");
    }

    public function testKolomWaktuDanUrut(): void
    {
        $kw = R::kolomWaktu(self::KOLOM_TAMU);
        $this->assertSame(['buat' => 'created_at', 'ubah' => 'modify_at', 'pk' => 'id'], $kw);
        $this->assertSame('COALESCE(`modify_at`, `created_at`) DESC, `id` DESC', R::urut($kw));
        $this->assertSame('`created_at` >= NOW() - INTERVAL 15 MINUTE OR `modify_at` >= NOW() - INTERVAL 15 MINUTE',
            R::syaratSejak($kw, 'NOW() - INTERVAL 15 MINUTE'));
        $this->assertSame('SELECT * FROM `tamu` WHERE `id` = 7 ORDER BY COALESCE(`modify_at`, `created_at`) DESC, `id` DESC LIMIT 10',
            R::sqlTerbaru('tamu', $kw, '`id` = 7', 10));

        // varchar bernama created_at bukan kolom waktu; tanpa PRI → tanpa urutan
        $kw2 = R::kolomWaktu([['nama' => 'created_at', 'data_type' => 'varchar', 'kunci' => '', 'ekstra' => '']]);
        $this->assertSame(['buat' => null, 'ubah' => null, 'pk' => null], $kw2);
        $this->assertSame('', R::urut($kw2));
        $this->assertSame('SELECT * FROM `ref` LIMIT 5', R::sqlTerbaru('ref', $kw2, '', 5));

        // PK komposit tanpa auto_increment → pk null; dengan auto_increment → yang itu
        $kw3 = R::kolomWaktu([['nama' => 'a', 'data_type' => 'int', 'kunci' => 'PRI', 'ekstra' => ''],
            ['nama' => 'b', 'data_type' => 'int', 'kunci' => 'PRI', 'ekstra' => '']]);
        $this->assertNull($kw3['pk']);
    }

    public function testNilaiSamarKolomKembar(): void
    {
        $this->assertSame('<biner 3 byte>', R::nilai("\xff\xfe\x00"));
        $this->assertSame(str_repeat('x', 10) . '…(+15 karakter)', R::nilai(str_repeat('x', 25), 10));
        $this->assertNull(R::nilai(null));
        $this->assertSame(7, R::nilai(7));
        $this->assertSame(['id', 'password', 'id#2', 'API_KEY'], R::kolomUnik(['id', 'password', 'id', 'API_KEY']));
        $this->assertTrue(R::rahasia('ssokey'));
        $this->assertTrue(R::rahasia('password#2'));
        $this->assertFalse(R::rahasia('nama'));

        [$rows, $samar] = R::barisDict(['id', 'password', 'id', 'API_KEY'], [[1, 'p', 2, null]]);
        $this->assertSame([['id' => 1, 'password' => '***disamarkan***', 'id#2' => 2, 'API_KEY' => null]], $rows);
        $this->assertSame(['password', 'API_KEY'], $samar);
    }

    public function testKolomSelaluKosong(): void
    {
        $rows = [
            ['id' => 7, 'nama' => 'Budi', 'instansi' => null, 'modify_at' => null, 'created_by' => null],
            ['id' => 6, 'nama' => 'Sari', 'instansi' => '', 'modify_at' => null, 'created_by' => 3],
        ];
        $this->assertSame(['instansi'], R::kolomSelaluKosong($rows, ['modify_at']));
        $this->assertSame([], R::kolomSelaluKosong([], ['modify_at']));
    }

    public function testCatatanStrukturKonvensiPhpvb(): void
    {
        $this->assertSame([], R::catatanStruktur(self::KOLOM_TAMU));

        $catatan = R::catatanStruktur([
            ['nama' => 'kode', 'data_type' => 'varchar', 'kunci' => 'PRI', 'ekstra' => ''],
            ['nama' => 'nama', 'data_type' => 'varchar', 'kunci' => '', 'ekstra' => ''],
            ['nama' => 'parent_id', 'data_type' => 'int', 'kunci' => '', 'ekstra' => ''],
        ]);
        $this->assertStringContainsString("kunci primer 'kode' bukan 'id'", $catatan[0]);
        $this->assertStringStartsWith('J08:', $catatan[1]);
        $this->assertStringContainsString('kolom audit phpVB tidak lengkap', $catatan[2]);
        $this->assertStringContainsString('parent_id', $catatan[3]);
    }

    public function testPangkasMenjagaUkuranBalasan(): void
    {
        $hasil = ['baris' => array_fill(0, 50, ['teks' => str_repeat('a', 100)])];
        $p = R::pangkas($hasil, 'baris', 2000);
        $this->assertLessThanOrEqual(2000, strlen(json_encode($p)));
        $this->assertLessThan(50, count($p['baris']));
        $this->assertArrayHasKey('dipangkas_ukuran', $p);
        $this->assertSame($hasil, R::pangkas($hasil, 'baris', 10000)); // muat → tak berubah
    }

    public function testQMengutipBacktick(): void
    {
        $this->assertSame('`ta``bel`', R::q('ta`bel'));
    }
}
