<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2session;
use Gov2lib\manifest;
use Gov2lib\manifestHandler;
use Gov2lib\Manifest\Endpoint;
use PHPUnit\Framework\TestCase;

/** Handler tiruan indeks: akar app = folder sementara; tanpa DSN; pemuatan komponen hanya dicatat. */
class ManifestFakeHandler extends manifestHandler
{
    public string $dir = '';
    public array $diambil = [];

    public function __construct()
    {
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

/** Sesi tiruan: authenticate() asli menyentuh DB untuk sesi ber-login; yang diuji pemanggilan gerbangnya. */
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

/**
 * Manifest per MVC (v5.1.7): cmd bawaan `manifest` (Gov2lib\Manifest\Endpoint) menyajikan view/{mvc}/{manifest|readme|release}.md;
 * /{app}/manifest = indeks endpoint MVC. Controller app ditiru dengan kelas di folder sementara (lokasi app = folder berkas kelas).
 */
class ManifestTest extends TestCase
{
    private static string $dir = '';
    private const FIX = __DIR__ . '/../../fixture/manifest/bukutamu';
    private array $prevServer;
    private ?array $mentah = null;

    public static function setUpBeforeClass(): void
    {
        self::$dir = sys_get_temp_dir() . '/phpvb-manifest-' . bin2hex(random_bytes(4)) . '/bukutamu';
        mkdir(self::$dir, 0777, true);
        $ns = self::ns();
        foreach (['kunjungan', 'jenis_keperluan', 'tanpadok'] as $mvc) {
            file_put_contents(self::$dir . "/{$mvc}.php", "<?php namespace {$ns}; class {$mvc} {}\n");
            require_once self::$dir . "/{$mvc}.php";
        }
        file_put_contents(self::$dir . '/index.php', "<?php\n");
    }

    public static function tearDownAfterClass(): void
    {
        exec('rm -rf ' . escapeshellarg(dirname(self::$dir)));
    }

    private static function ns(): string
    {
        return 'App\\ujimanifest' . substr(md5(self::$dir), 0, 6);
    }

    protected function setUp(): void
    {
        $this->prevServer = $_SERVER;
        exec('rm -rf ' . escapeshellarg(self::$dir . '/view'));
        $GLOBALS['pageID'] = 'bukutamu';
        $GLOBALS['vars'] = ['app' => 'bukutamu', 'cmd' => ''];
        $GLOBALS['request'] = 'ajax';
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain/></config>');
        $GLOBALS['doc'] = new \Gov2lib\document();
        $h = new ManifestFakeHandler();
        $h->dir = self::$dir;
        $h->ses = new ManifestFakeSession();
        $GLOBALS['self'] = $h;
        $_SERVER['SERVER_NAME'] = 'becik.uji';
        http_response_code(200);
        Endpoint::$kirimMentah = function (string $isi, string $nama) {
            $this->mentah = ['isi' => $isi, 'nama' => $nama];

            return $isi;
        };
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->prevServer;
        Endpoint::$kirimMentah = null;
        unset($GLOBALS['self'], $GLOBALS['pageID'], $GLOBALS['vars'], $GLOBALS['request'], $GLOBALS['config'], $GLOBALS['doc']);
    }

    private function salinFixture(string ...$mvc): void
    {
        foreach ($mvc as $m) {
            @mkdir(self::$dir . "/view/{$m}", 0777, true);
            foreach (['manifest.md', 'release.md', 'readme.md'] as $f) {
                copy(self::FIX . "/view/{$m}/{$f}", self::$dir . "/view/{$m}/{$f}");
            }
        }
    }

    private function controller(string $mvc): object
    {
        $kelas = self::ns() . '\\' . $mvc;

        return new $kelas();
    }

    private function login(): void
    {
        $GLOBALS['self']->ses->val = ['account_id' => 4, 'id' => 1, 'userRole' => 'guest', 'status' => 'active'];
    }

    // ---------------------------------------------------------------- endpoint per MVC

    public function testArgumenDariSegmenRuteDanPost(): void
    {
        self::assertSame(['manifest', false], Endpoint::argumen(['cmd' => 'manifest']));
        self::assertSame(['readme', false], Endpoint::argumen(['id' => 'readme']));
        self::assertSame(['manifest', true], Endpoint::argumen(['id' => 'raw']));
        self::assertSame(['release', true], Endpoint::argumen(['scroll' => 'release', 'id' => 'raw']));
        self::assertSame(['readme', true], Endpoint::argumen(['cmd' => 'manifest', 'jenis' => 'readme', 'format' => 'raw']));
        self::assertSame(['manifest', false], Endpoint::argumen(['id' => '7']));
    }

    public function testManifestJsonPerMvc(): void
    {
        $this->salinFixture('kunjungan');
        $r = Endpoint::sajikan($this->controller('kunjungan'), ['cmd' => 'manifest']);

        self::assertTrue($r['ok'], json_encode($r));
        self::assertSame(['bukutamu', 'kunjungan', 'manifest', '1.2.0'], [$r['app'], $r['mvc'], $r['jenis'], $r['versi']]);
        self::assertSame(['manifest' => true, 'readme' => true, 'release' => true], $r['berkas']);
        self::assertSame('/bukutamu/kunjungan/manifest', $r['url']['manifest']);
        self::assertSame('/bukutamu/kunjungan/manifest/readme', $r['url']['readme']);
        self::assertSame('edit', $r['data']['cmd']['del']['konfirmasi']);
        self::assertSame('kunjungan', $r['app_data']['mvc']);
        self::assertSame([], $r['galat']);
        self::assertStringStartsWith('# Manifest API — bukutamu/kunjungan v1.2.0', $r['markdown']);
    }

    public function testReadmeReleaseDanRaw(): void
    {
        $this->salinFixture('kunjungan');
        $c = $this->controller('kunjungan');

        $r = Endpoint::sajikan($c, ['id' => 'release']);
        self::assertSame('release', $r['jenis']);
        self::assertSame('1.2.0', $r['versi']);
        self::assertArrayNotHasKey('data', $r);
        self::assertStringStartsWith('# Riwayat rilis — bukutamu/kunjungan', $r['markdown']);

        $r = Endpoint::sajikan($c, ['id' => 'readme']);
        self::assertSame('readme', $r['jenis']);
        self::assertStringContainsString('buku tamu harian', $r['markdown']);

        Endpoint::sajikan($c, ['scroll' => 'readme', 'id' => 'raw']);
        self::assertSame('bukutamu-kunjungan-readme.md', $this->mentah['nama']);
        self::assertSame((string) file_get_contents(self::FIX . '/view/kunjungan/readme.md'), $this->mentah['isi']);
        Endpoint::sajikan($c, ['id' => 'raw']);
        self::assertSame('bukutamu-kunjungan-manifest.md', $this->mentah['nama']);
    }

    public function testBerkasHilang404BernamaDanHalamanPemberitahuan(): void
    {
        $r = Endpoint::sajikan($this->controller('tanpadok'), ['cmd' => 'manifest']);
        self::assertSame(404, http_response_code());
        self::assertSame('ManifestNotFound', $r['error']);
        self::assertStringContainsString('apps/bukutamu/view/tanpadok/manifest.md', $r['message']);
        self::assertSame('/bukutamu/tanpadok/manifest', $r['url']['manifest']);

        $GLOBALS['request'] = 'page';
        $r = Endpoint::sajikan($this->controller('tanpadok'), ['id' => 'readme']);
        self::assertTrue($r['ok']);
        self::assertStringContainsString('view/tanpadok/readme.md', $GLOBALS['doc']->body['manifestHtml']);
    }

    public function testHalamanTanpaTombolDanTanpaBlokData(): void
    {
        $this->salinFixture('kunjungan');
        $GLOBALS['request'] = 'page';
        $r = Endpoint::sajikan($this->controller('kunjungan'), []);
        $doc = $GLOBALS['doc'];

        self::assertTrue($r['ok']);
        self::assertSame('Manifest API — bukutamu/kunjungan v1.2.0', $doc->body['pageTitle']);
        self::assertStringContainsString('<h3>kunjungan — Kunjungan Tamu</h3>', $doc->body['manifestHtml']);
        self::assertStringNotContainsString('"skema"', $doc->body['manifestHtml']);
        self::assertContains('manifest.html', $doc->content);
        self::assertArrayNotHasKey('manifestTautan', $doc->body, 'tanpa tombol navigasi');
        $tpl = (string) file_get_contents(__DIR__ . '/../../../core/template/general/manifest.html');
        self::assertStringNotContainsString('btn', $tpl);
    }

    public function testDispatcherMemanggilEndpointHanyaBilaControllerTakPunyaMethod(): void
    {
        $src = (string) file_get_contents(__DIR__ . '/../../../core/init/index.php');
        $i = strpos($src, 'method_exists(${$pageID},$cmd)');
        $j = strpos($src, 'Gov2lib\\Manifest\\Endpoint::sajikan');
        self::assertNotFalse($i);
        self::assertNotFalse($j);
        self::assertGreaterThan($i, $j, 'method milik controller diperiksa lebih dulu');
        self::assertStringContainsString("str_starts_with(\$controller, 'App\\\\')", $src);
    }

    // ---------------------------------------------------------------- indeks app

    public function testIndeksDanPing(): void
    {
        $this->salinFixture('kunjungan', 'jenis_keperluan');
        $c = new manifest();
        $p = $c->ping([]);
        self::assertSame(['jenis_keperluan', 'kunjungan'], $p['mvc_bermanifest']);
        self::assertSame(['tanpadok'], $p['tanpa_manifest']);

        $c->index([]);
        self::assertSame(401, http_response_code(), 'indeks butuh login');
        self::assertSame(['guest'], $GLOBALS['self']->ses->dipanggil);

        http_response_code(200);
        $GLOBALS['doc'] = new \Gov2lib\document();
        $this->login();
        $r = $c->index([]);
        self::assertTrue($r['ok'], json_encode($r));
        self::assertSame(2, $r['jumlah']);
        self::assertSame('1.2.0', $r['mvc']['kunjungan']['versi']);
        self::assertSame('/bukutamu/kunjungan/manifest', $r['mvc']['kunjungan']['url']['manifest']);
        self::assertContains('del', $r['mvc']['kunjungan']['cmd']);
        self::assertSame(['tanpadok'], $r['tanpa_manifest']);
    }

    public function testIndeksHalamanDanPublik(): void
    {
        $this->salinFixture('kunjungan');
        $GLOBALS['config']->addChild('manifest', 'public');
        $GLOBALS['request'] = 'page';
        $r = (new manifest())->index([]);
        self::assertTrue($r['ok']);
        self::assertSame([], $GLOBALS['self']->ses->dipanggil, 'public: tanpa authenticate');
        $html = $GLOBALS['doc']->body['manifestHtml'];
        self::assertStringContainsString('/bukutamu/kunjungan/manifest', $html);
        self::assertStringContainsString('tanpadok', $html);
        self::assertSame(['takeAll:components', 'take:bukutamu/index/dependencies'], $GLOBALS['self']->diambil);
    }
}
