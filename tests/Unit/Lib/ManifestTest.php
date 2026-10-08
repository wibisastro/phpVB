<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2session;
use Gov2lib\manifest;
use Gov2lib\manifestHandler;
use PHPUnit\Framework\TestCase;

/** Handler tiruan: akar app = folder sementara; tanpa DSN; pemuatan komponen tata letak hanya dicatat. */
class ManifestFakeHandler extends manifestHandler
{
    public string $dir = '';
    public array $diambil = [];

    public function __construct()
    {
        // lewati checkExist/dsnSource (butuh dsnSource.xml) — hanya properti yang dipakai controller
        $this->templateDir = '';
    }

    public function dirApp(): string
    {
        return $this->dir;
    }

    public function takeAll(string $appDir): void
    {
        $this->diambil[] = "takeAll:{$appDir}";
    }

    public function take(string $appDir, string $class = '', string $fn = '', mixed $param = '', string $dsn = ''): void
    {
        $this->diambil[] = "take:{$appDir}/{$class}/{$fn}";
    }
}

/**
 * Sesi tiruan: authenticate() asli selalu menyentuh DB untuk sesi ber-login (memberUpdateCounter,
 * getRoleLevel) — yang diuji di sini adalah PEMANGGILAN gerbang oleh controller, bukan gov2session.
 */
class ManifestFakeSession extends gov2session
{
    public array $dipanggil = [];

    public function authenticate(string $_privilege = 'member', string $_maintenance = ''): void
    {
        global $doc;

        $this->dipanggil[] = $_privilege;
        if ($_privilege !== 'public' && empty($this->val['id'])) {
            $doc->error('NotLogin', 'Halaman BUKUTAMU harus login terlebih dahulu');
        }
    }

    public function takeAll(string $appDir): void
    {
    }
}

/** Controller tiruan: tangkap keluaran mentah alih-alih echo + exit. */
class ManifestTanpaExit extends manifest
{
    public ?array $mentah = null;

    protected function kirimMentah(string $isi, string $nama): mixed
    {
        $this->mentah = ['isi' => $isi, 'nama' => $nama];

        return $isi;
    }
}

class ManifestTest extends TestCase
{
    private string $dir;
    private array $prevServer;

    protected function setUp(): void
    {
        $this->prevServer = $_SERVER;
        $this->dir = sys_get_temp_dir() . '/phpvb-manifest-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/view', 0777, true);
        $GLOBALS['pageID'] = 'bukutamu';
        $GLOBALS['vars'] = ['app' => 'bukutamu', 'cmd' => ''];
        $GLOBALS['request'] = 'ajax';
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain/></config>');
        $GLOBALS['doc'] = new \Gov2lib\document();
        $h = new ManifestFakeHandler();
        $h->dir = $this->dir;
        $h->ses = new ManifestFakeSession();
        $GLOBALS['self'] = $h;
        $_SERVER['SERVER_NAME'] = 'becik.uji';
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->prevServer;
        foreach (['view/manifest.md', 'view/release.md', 'README.md'] as $f) {
            @unlink("{$this->dir}/{$f}");
        }
        @rmdir("{$this->dir}/view");
        @rmdir($this->dir);
        unset($GLOBALS['self'], $GLOBALS['pageID'], $GLOBALS['vars'], $GLOBALS['request'], $GLOBALS['config'], $GLOBALS['doc']);
    }

    private function tulis(string $rel, string $isi): void
    {
        file_put_contents("{$this->dir}/{$rel}", $isi);
    }

    private function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/../../fixture/manifest/bukutamu.md');
    }

    /** Sesi login tiruan: authenticate('guest') lolos bila cookie memuat id + userRole (tanpa DB). */
    private function login(): void
    {
        $GLOBALS['self']->ses->val = ['account_id' => 4, 'id' => 1, 'userRole' => 'guest', 'status' => 'active', 'fullname' => 'Uji'];
    }

    public function testPingTanpaLoginMelaporkanBerkas(): void
    {
        $c = new ManifestTanpaExit();
        $r = $c->ping([]);
        self::assertTrue($r['ok']);
        self::assertSame(['manifest' => false, 'release' => false, 'readme' => false], $r['berkas']);
        self::assertSame('', $r['versi']);

        $this->tulis('view/manifest.md', $this->fixture());
        $this->tulis('README.md', "# Buku Tamu\n");
        $r = $c->ping([]);
        self::assertSame(['manifest' => true, 'release' => false, 'readme' => true], $r['berkas']);
        self::assertSame('1.2.0', $r['versi']);
        self::assertSame(1, $r['skema']);
    }

    public function testTanpaLoginDitolak401(): void
    {
        $this->tulis('view/manifest.md', $this->fixture());
        $c = new ManifestTanpaExit();
        $r = $c->index([]);
        self::assertSame(401, http_response_code());
        self::assertSame('is-danger', $r['class']);
        self::assertStringContainsString('NotLogin', (string) $r['notification']);
        self::assertArrayNotHasKey('markdown', $r);
        self::assertSame(['guest'], $GLOBALS['self']->ses->dipanggil);
    }

    public function testPublicLewatConfigAppTanpaLogin(): void
    {
        $GLOBALS['config']->addChild('manifest', 'public');
        $this->tulis('view/manifest.md', $this->fixture());
        $c = new ManifestTanpaExit();
        $r = $c->index([]);
        self::assertTrue($r['ok']);
        self::assertSame('manifest', $r['jenis']);
        self::assertSame([], $GLOBALS['self']->ses->dipanggil, 'public: authenticate tidak dipanggil');
    }

    public function testIndeksJsonMemuatMdDanDataTerurai(): void
    {
        $this->login();
        $this->tulis('view/manifest.md', $this->fixture());
        $this->tulis('view/release.md', "# Riwayat\n\n## v1.2.0 — 2026-10-08\n- kunjungan: cari (M5)\n");
        $c = new ManifestTanpaExit();
        $r = $c->index([]);

        self::assertTrue($r['ok']);
        self::assertSame('bukutamu', $r['app']);
        self::assertSame('1.2.0', $r['versi']);
        self::assertSame(['manifest' => true, 'release' => true, 'readme' => false], $r['berkas']);
        self::assertSame(['jenis_keperluan', 'kunjungan'], array_keys($r['mvc']));
        self::assertSame('POST', $r['data']['kunjungan']['cmd']['del']['method']);
        self::assertSame('edit', $r['data']['kunjungan']['cmd']['del']['konfirmasi']);
        self::assertSame([], $r['galat']);
        self::assertSame('/bukutamu/manifest/mvc/{nama}', $r['url']['mvc']);
        self::assertStringContainsString('<!-- manifest:mvc:kunjungan start -->', $r['markdown']);
        self::assertSame('bukutamu', $r['app_data']['app']);
    }

    public function testSatuMvcDanMvcTakDikenal(): void
    {
        $this->login();
        $this->tulis('view/manifest.md', $this->fixture());
        $c = new ManifestTanpaExit();

        $r = $c->mvc(['id' => 'kunjungan']);
        self::assertTrue($r['ok']);
        self::assertSame('kunjungan', $r['mvc']);
        self::assertSame(['tambah', 'ubah', 'hapus', 'cari'], array_keys($r['data']['alur']));
        self::assertStringStartsWith('### kunjungan', $r['markdown']);

        $r = $c->mvc(['id' => 'tidak_ada']);
        self::assertSame(404, http_response_code());
        self::assertSame('MvcTidakAda', $r['error']);
        self::assertSame(['jenis_keperluan', 'kunjungan'], $r['mvc_tersedia']);

        $r = $c->mvc(['id' => '../etc']);
        self::assertSame('MvcTidakValid', $r['error']);
    }

    public function testRawMengirimMarkdownMentah(): void
    {
        $this->login();
        $this->tulis('view/manifest.md', $this->fixture());
        $this->tulis('README.md', "# Buku Tamu\n\nKonteks.\n");
        $c = new ManifestTanpaExit();

        $c->raw([]);
        self::assertSame($this->fixture(), $c->mentah['isi']);
        self::assertSame('bukutamu-manifest.md', $c->mentah['nama']);

        $c->readme(['id' => 'raw']);
        self::assertSame("# Buku Tamu\n\nKonteks.\n", $c->mentah['isi']);
        self::assertSame('bukutamu-readme.md', $c->mentah['nama']);
    }

    public function testBerkasHilang404Bernama(): void
    {
        $this->login();
        $c = new ManifestTanpaExit();
        $r = $c->index([]);
        self::assertSame(404, http_response_code());
        self::assertSame('ManifestNotFound', $r['error']);
        self::assertStringContainsString('apps/bukutamu/view/manifest.md', $r['message']);

        $r = $c->release([]);
        self::assertSame('release', $r['jenis']);
        self::assertStringContainsString('view/release.md', $r['message']);
    }

    public function testReleaseDanReadmeJsonTanpaUrai(): void
    {
        $this->login();
        $this->tulis('view/release.md', "# Riwayat\n\n## v0.3.0 — 2026-10-01\n- awal\n");
        $this->tulis('README.md', "# Buku Tamu\n");
        $c = new ManifestTanpaExit();

        $r = $c->release([]);
        self::assertTrue($r['ok']);
        self::assertSame('0.3.0', $r['versi']);
        self::assertArrayNotHasKey('mvc', $r);
        self::assertStringStartsWith('# Riwayat', $r['markdown']);

        $r = $c->readme([]);
        self::assertSame('readme', $r['jenis']);
        self::assertSame("# Buku Tamu\n", $r['markdown']);
    }

    public function testHalamanMendaftarkanKontenDanMenyembunyikanBlokData(): void
    {
        $this->login();
        $GLOBALS['request'] = 'page';
        $this->tulis('view/manifest.md', $this->fixture());
        $c = new ManifestTanpaExit();
        $r = $c->index([]);

        self::assertTrue($r['ok']);
        $doc = $GLOBALS['doc'];
        self::assertSame('Manifest API — bukutamu v1.2.0', $doc->body['pageTitle']);
        self::assertStringContainsString('<h3>kunjungan — Kunjungan Tamu</h3>', $doc->body['manifestHtml']);
        self::assertStringNotContainsString('"skema"', $doc->body['manifestHtml']);
        self::assertStringNotContainsString('manifest:mvc', $doc->body['manifestHtml']);
        self::assertSame('Manifest', $doc->body['manifestTautan'][0]['label']);
        // content() memberi prefix @{namespace} dari get_called_class() — di uji namespace Tests, di runtime Gov2lib\manifestHandler (tanpa prefix)
        self::assertNotEmpty(array_filter($doc->content, fn($c) => str_ends_with((string) $c, 'manifest.html')));
        self::assertSame(['takeAll:components', 'take:bukutamu/index/dependencies'], $GLOBALS['self']->diambil, 'tata letak app dimuat hanya pada mode halaman');

        $GLOBALS['doc'] = new \Gov2lib\document();
        @unlink("{$this->dir}/view/manifest.md");
        $r = $c->index([]);
        self::assertFalse($r['ok']);
        self::assertStringContainsString('belum ada', $GLOBALS['doc']->body['manifestHtml']);
    }
}
