<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\agendb;
use Gov2lib\agendbHandler;
use Gov2lib\Database\AgendbException;
use Gov2lib\gov2session;
use PHPUnit\Framework\TestCase;

/**
 * Handler tiruan: tanpa mysqli (PHP CLI laptop tidak punya ekstensi itu). Yang diuji di sini adalah
 * GERBANG controller (rahasia, identitas, peran/superuser) dan bentuk balasan — bukan SQL.
 */
class AgendbFakeHandler extends agendbHandler
{
    /** @var array<string, array<int, array<string, mixed>>> email → baris member */
    public array $member = [];
    public array $dibuka = [];
    public array $panggilan = [];
    public bool $ditutup = false;
    public ?AgendbException $galatBuka = null;

    public function __construct()
    {
        // parent (dsnSource/document) butuh $pageID + $config global — disediakan test
        parent::__construct();
    }

    public function buka(string $dsn = ''): \mysqli
    {
        if ($this->galatBuka) {
            throw $this->galatBuka;
        }
        $this->dibuka[] = $dsn ?: self::dsnBawaan();
        // tanpa ekstensi mysqli: kelas pengganti bernama sama hanya untuk memenuhi tipe balik;
        // dengan ekstensi: new mysqli() tanpa argumen = objek belum tersambung (mysqli_init)
        if (!class_exists('mysqli', false)) {
            eval('class mysqli {}');
        }

        return new \mysqli();
    }

    public function tutup(): void
    {
        $this->ditutup = true;
    }

    public function dbAktif(): string
    {
        return 'bukutamu_staging';
    }

    public function cariMember(string $email): array
    {
        $this->panggilan[] = ['cariMember', $email];

        return $this->member[strtolower($email)] ?? [];
    }

    public function daftarDsn(): array
    {
        return [['nama' => 'master', 'driver' => 'meekro', 'db' => 'gov3_central', 'host' => 'localhost', 'bawaan' => false],
            ['nama' => 'becik.uji', 'driver' => 'meekro', 'db' => 'bukutamu_staging', 'host' => 'localhost', 'bawaan' => true]];
    }

    public function kueri(string $sql, int $batas): array
    {
        $this->panggilan[] = ['kueri', $sql, $batas];

        return ['kolom' => ['id', 'nama'], 'jumlah' => 1, 'terpotong' => false, 'ms' => 1, 'baris' => [['id' => 7, 'nama' => 'Budi Rahasia']], 'disamarkan' => []];
    }

    public function terbaru(string $tabel, string $sejak, int $batas): array
    {
        $this->panggilan[] = ['terbaru', $tabel, $sejak, $batas];

        return ['tabel' => $tabel, 'baris' => []];
    }
}

class AgendbTest extends TestCase
{
    private const SECRET = 'rahasia-uji-agendb';
    private string $logFile;
    private array $prevServer;

    protected function setUp(): void
    {
        $this->prevServer = $_SERVER;
        $GLOBALS['pageID'] = 'bukutamu';
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain/></config>');
        $GLOBALS['config']->domain->attr['dsn'] = 'becik.uji';
        $GLOBALS['doc'] = new \Gov2lib\document();
        $this->logFile = tempnam(sys_get_temp_dir(), 'agendb-audit-');
        ini_set('error_log', $this->logFile);
        putenv('PHPVB_AGENDB_SECRET=' . self::SECRET);
        unset($_SERVER[agendb::H_SECRET], $_SERVER['HTTP_X_GOV3_USER'], $_SERVER['HTTP_X_GOV3_SESSION']);
        $_SERVER['SERVER_NAME'] = 'becik.uji';

        $h = new AgendbFakeHandler();
        $h->ses = new gov2session();
        $h->member = [
            'fathur@contoh.id' => [['id' => 5, 'account_id' => 9, 'fullname' => 'Fathur', 'email' => 'fathur@contoh.id', 'role' => 'member', 'status' => 'active']],
            'wibi@contoh.id' => [['id' => 1, 'account_id' => 4, 'fullname' => 'Wibi', 'email' => 'wibi@contoh.id', 'role' => 'webmaster', 'status' => 'active']],
            'tamu@instansi.go.id' => [['id' => 8, 'account_id' => 77, 'fullname' => 'Tamu', 'email' => 'tamu@instansi.go.id', 'role' => 'guest', 'status' => 'active']],
            'blokir@contoh.id' => [['id' => 9, 'account_id' => 78, 'fullname' => 'B', 'email' => 'blokir@contoh.id', 'role' => 'webmaster', 'status' => 'suspended']],
        ];
        $GLOBALS['self'] = $h;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->prevServer;
        putenv('PHPVB_AGENDB_SECRET');
        ini_restore('error_log');
        @unlink($this->logFile);
        unset($GLOBALS['self'], $GLOBALS['pageID'], $GLOBALS['config'], $GLOBALS['doc']);
    }

    private function handler(): AgendbFakeHandler
    {
        return $GLOBALS['self'];
    }

    private function sebagai(string $email, bool $secret = true): void
    {
        if ($secret) {
            $_SERVER[agendb::H_SECRET] = self::SECRET;
        }
        $_SERVER['HTTP_X_GOV3_USER'] = $email;
        $_SERVER['HTTP_X_GOV3_SESSION'] = 'ses_uji';
    }

    private function audit(): array
    {
        $baris = array_values(array_filter(array_map('trim', file($this->logFile) ?: [])));
        $json = [];
        foreach ($baris as $b) {
            $pos = strpos($b, '{');
            $d = $pos === false ? null : json_decode(substr($b, $pos), true);
            if (is_array($d) && ($d['ev'] ?? '') === 'phpvb.agendb') {
                $json[] = $d;
            }
        }

        return $json;
    }

    public function testPingTanpaIdentitasTidakBocorkanRahasia(): void
    {
        $r = (new agendb())->ping([]);
        $this->assertTrue($r['ok']);
        $this->assertTrue($r['secret_configured']);
        $this->assertSame('becik.uji', $r['dsn_bawaan']);
        $this->assertStringNotContainsString(self::SECRET, json_encode($r));
        $this->assertSame([], $this->handler()->panggilan); // DB tidak disentuh
    }

    public function testMatiTanpaRahasiaServer(): void
    {
        putenv('PHPVB_AGENDB_SECRET');
        $this->sebagai('wibi@contoh.id');
        $r = (new agendb())->dsn([]);
        $this->assertSame('AgenDbDisabled', $r['error']);
        $this->assertFalse($r['ok']);
        $this->assertSame(503, http_response_code());
        $this->assertSame([], $this->handler()->panggilan);
    }

    public function testRahasiaSalahDitolakSebelumMenyentuhDb(): void
    {
        $this->sebagai('wibi@contoh.id', secret: false);
        $_SERVER[agendb::H_SECRET] = 'salah';
        $r = (new agendb())->kueri(['sql' => 'SELECT 1']);
        $this->assertSame('BadSecret', $r['error']);
        $this->assertSame(403, http_response_code());
        $this->assertSame([], $this->handler()->panggilan);
    }

    public function testTanpaEmailDitolak(): void
    {
        $_SERVER[agendb::H_SECRET] = self::SECRET;
        $r = (new agendb())->tabel([]);
        $this->assertSame('BLOCKED_NO_IDENTITY', $r['error']);
        $this->assertSame([], $this->handler()->panggilan);
    }

    public function testEmailTakTerdaftarDanBukanPengembangDitolak(): void
    {
        $this->sebagai('asing@contoh.id');
        $this->assertSame('NoAccount', (new agendb())->dsn([])['error']);

        $this->sebagai('tamu@instansi.go.id');
        $r = (new agendb())->dsn([]);
        $this->assertSame('BLOCKED_BUKAN_PENGEMBANG', $r['error']);
        $this->assertStringContainsString("'guest'", $r['message']);
        $this->assertSame(403, http_response_code());

        $this->sebagai('blokir@contoh.id');
        $this->assertSame('BLOCKED_STATUS', (new agendb())->dsn([])['error']);

        // hanya cariMember yang dipanggil — DSN app tidak pernah dibuka untuk yang ditolak
        $this->assertSame([], $this->handler()->dibuka);
    }

    public function testWebmasterLolosDanKueriDiteruskan(): void
    {
        $this->sebagai('wibi@contoh.id');
        $r = (new agendb())->kueri(['sql' => 'SELECT id, nama FROM tamu', 'batas' => 5]);
        $this->assertTrue($r['ok'], json_encode($r));
        $this->assertSame(['app' => 'bukutamu', 'dsn' => 'becik.uji', 'db' => 'bukutamu_staging'], ['app' => $r['app'], 'dsn' => $r['dsn'], 'db' => $r['db']]);
        $this->assertSame(7, $r['baris'][0]['id']);
        $this->assertSame(['becik.uji'], $this->handler()->dibuka);
        $panggilan = $this->handler()->panggilan;
        $this->assertSame(['kueri', 'SELECT id, nama FROM tamu', 5], end($panggilan));
        $this->assertTrue($this->handler()->ditutup);

        $audit = $this->audit();
        $this->assertNotEmpty($audit);
        $akhir = $audit[count($audit) - 1];
        $this->assertSame(['kueri', 'ok', 'wibi@contoh.id', 'webmaster', 'ses_uji'], [$akhir['cmd'], $akhir['hasil'], $akhir['email'], $akhir['role'], $akhir['session']]);
        $this->assertStringNotContainsString('Budi Rahasia', file_get_contents($this->logFile)); // isi baris tidak masuk audit
    }

    public function testMemberBiasaLolosBilaTercantumDiSuperuserXml(): void
    {
        $dir = __DIR__ . '/../../../apps/bukutamu/xml';
        $ada = is_dir($dir);
        if (!$ada) {
            mkdir($dir, 0777, true);
        }
        $file = $dir . '/superuser.xml';
        $lama = file_exists($file) ? file_get_contents($file) : null;
        file_put_contents($file, "<?xml version=\"1.0\"?>\n<superuser><role name=\"webmaster\"><account_id>9</account_id></role></superuser>\n");
        try {
            $this->sebagai('fathur@contoh.id'); // role member di tabel, account_id 9 = pemilik app
            $r = (new agendb())->terbaru(['tabel' => 'tamu', 'sejak' => '15m']);
            $this->assertTrue($r['ok'], json_encode($r));
            $panggilan = $this->handler()->panggilan;
            $audit = $this->audit();
            $this->assertSame(['terbaru', 'tamu', '15m', 10], end($panggilan));
            $this->assertSame('webmaster', $audit[count($audit) - 1]['superuser']);
        } finally {
            if ($lama === null) {
                unlink($file);
                if (!$ada) {
                    @rmdir($dir);
                    @rmdir(dirname($dir));
                }
            } else {
                file_put_contents($file, $lama);
            }
        }
    }

    public function testArgumenTabelDivalidasiSebelumGerbang(): void
    {
        $this->sebagai('wibi@contoh.id');
        $r = (new agendb())->terbaru(['tabel' => 'tamu; DROP TABLE x']);
        $this->assertSame('TABEL_TIDAK_VALID', $r['error']);
        $this->assertSame([], $this->handler()->panggilan);
        $r = (new agendb())->struktur([]);
        $this->assertSame('TABEL_TIDAK_VALID', $r['error']);
    }

    public function testGalatBukaDsnDilaporkanTerstruktur(): void
    {
        $this->sebagai('wibi@contoh.id');
        $this->handler()->galatBuka = new AgendbException('DriverTidakDidukung', 'entri berdriver supabase', 501);
        $r = (new agendb())->dsn([]);
        $this->assertSame(['DriverTidakDidukung', false], [$r['error'], $r['ok']]);
        $this->assertSame(501, http_response_code());
        $this->assertTrue($this->handler()->ditutup);
    }
}
