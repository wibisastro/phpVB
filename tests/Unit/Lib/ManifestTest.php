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
 * Dokumen per MVC (v5.1.8): cmd bawaan `readme` (Gov2lib\Manifest\Endpoint) menyajikan view/{mvc}/{readme|manifest|release|spec}.md
 * (akar = readme; alias v5.1.7 cmd `manifest` akarnya manifest); /{app}/readme (alias /{app}/manifest) = indeks endpoint MVC. Controller app ditiru dengan kelas di folder sementara (lokasi app = folder berkas kelas).
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
            foreach (glob(self::FIX . "/view/{$m}/*.md") as $f) {
                copy($f, self::$dir . "/view/{$m}/" . basename($f));
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
        // v5.1.8: akar = readme
        self::assertSame(['readme', false], Endpoint::argumen(['cmd' => 'readme']));
        self::assertSame(['readme', false], Endpoint::argumen([]));
        self::assertSame(['readme', true], Endpoint::argumen(['cmd' => 'readme', 'id' => 'raw']));
        self::assertSame(['manifest', false], Endpoint::argumen(['cmd' => 'readme', 'id' => 'manifest']));
        self::assertSame(['spec', false], Endpoint::argumen(['cmd' => 'readme', 'id' => 'spec']));
        self::assertSame(['spec', true], Endpoint::argumen(['cmd' => 'readme', 'scroll' => 'spec', 'id' => 'raw']));
        self::assertSame(['release', true], Endpoint::argumen(['scroll' => 'release', 'id' => 'raw']));
        self::assertSame(['spec', true], Endpoint::argumen(['cmd' => 'readme', 'jenis' => 'spec', 'format' => 'raw']));
        self::assertSame(['readme', false], Endpoint::argumen(['id' => '7']));
        // alias v5.1.7: cmd manifest — akar manifest
        self::assertSame(['manifest', false], Endpoint::argumen(['cmd' => 'manifest']));
        self::assertSame(['manifest', true], Endpoint::argumen(['cmd' => 'manifest', 'id' => 'raw']));
        self::assertSame(['readme', true], Endpoint::argumen(['cmd' => 'manifest', 'jenis' => 'readme', 'format' => 'raw']));
        self::assertSame(['readme', 'manifest'], Endpoint::CMDS);
    }

    public function testManifestJsonPerMvc(): void
    {
        $this->salinFixture('kunjungan');
        $r = Endpoint::sajikan($this->controller('kunjungan'), ['cmd' => 'readme', 'id' => 'manifest']);

        self::assertTrue($r['ok'], json_encode($r));
        self::assertSame(['bukutamu', 'kunjungan', 'manifest', '1.2.0'], [$r['app'], $r['mvc'], $r['jenis'], $r['versi']]);
        self::assertSame(['readme' => true, 'manifest' => true, 'release' => true, 'spec' => true], $r['berkas']);
        self::assertSame(['readme' => '/bukutamu/kunjungan/readme', 'manifest' => '/bukutamu/kunjungan/readme/manifest',
            'release' => '/bukutamu/kunjungan/readme/release', 'spec' => '/bukutamu/kunjungan/readme/spec', 'raw' => '/bukutamu/kunjungan/readme/raw',
            'indeks_app' => '/bukutamu/readme'], $r['url']);
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
        Endpoint::sajikan($c, ['cmd' => 'readme', 'id' => 'raw']);
        self::assertSame('bukutamu-kunjungan-readme.md', $this->mentah['nama'], 'akar readme: /readme/raw = readme mentah');
        Endpoint::sajikan($c, ['cmd' => 'manifest', 'id' => 'raw']);
        self::assertSame('bukutamu-kunjungan-manifest.md', $this->mentah['nama'], 'alias lama: /manifest/raw = manifest mentah');
    }

    public function testSpecJsonHalamanDanMentahSalinBerkas(): void
    {
        $this->salinFixture('kunjungan');
        $c = $this->controller('kunjungan');
        $asli = (string) file_get_contents(self::FIX . '/view/kunjungan/spec.md');

        $r = Endpoint::sajikan($c, ['cmd' => 'readme', 'id' => 'spec']);
        self::assertTrue($r['ok']);
        self::assertSame('spec', $r['jenis']);
        self::assertSame($asli, $r['markdown']);
        self::assertArrayNotHasKey('data', $r);

        Endpoint::sajikan($c, ['cmd' => 'readme', 'scroll' => 'spec', 'id' => 'raw']);
        self::assertSame('bukutamu-kunjungan-spec.md', $this->mentah['nama']);
        self::assertSame($asli, $this->mentah['isi'], 'salin /readme/spec/raw = salin berkas spec.md');

        $GLOBALS['request'] = 'page';
        Endpoint::sajikan($c, ['cmd' => 'readme', 'id' => 'spec']);
        $doc = $GLOBALS['doc'];
        self::assertSame('Spec — bukutamu/kunjungan', $doc->body['pageTitle']);
        self::assertStringContainsString('Di app bukutamu, buat halaman', $doc->body['manifestHtml']);
        self::assertStringNotContainsString('spec phpVB:', $doc->body['manifestHtml'], 'komentar kepala tidak tampil');
        self::assertStringNotContainsString('spec:rincian', $doc->body['manifestHtml']);
    }

    public function testBerkasHilang404BernamaDanHalamanPemberitahuan(): void
    {
        $r = Endpoint::sajikan($this->controller('tanpadok'), ['cmd' => 'readme', 'id' => 'manifest']);
        self::assertSame(404, http_response_code());
        self::assertSame('ManifestNotFound', $r['error']);
        self::assertStringContainsString('apps/bukutamu/view/tanpadok/manifest.md', $r['message']);
        self::assertSame('/bukutamu/tanpadok/readme/manifest', $r['url']['manifest']);
        $r = Endpoint::sajikan($this->controller('tanpadok'), ['cmd' => 'readme', 'id' => 'spec']);
        self::assertStringContainsString('view/tanpadok/spec.md', $r['message']);
        self::assertStringContainsString('spec=', $r['message']);

        $GLOBALS['request'] = 'page';
        $r = Endpoint::sajikan($this->controller('tanpadok'), ['id' => 'readme']);
        self::assertTrue($r['ok']);
        self::assertStringContainsString('view/tanpadok/readme.md', $GLOBALS['doc']->body['manifestHtml']);
    }

    public function testHalamanTanpaTombolDanTanpaBlokData(): void
    {
        $this->salinFixture('kunjungan');
        $GLOBALS['request'] = 'page';
        $r = Endpoint::sajikan($this->controller('kunjungan'), ['cmd' => 'readme', 'id' => 'manifest']);
        $doc = $GLOBALS['doc'];

        self::assertTrue($r['ok']);
        self::assertSame('Manifest API — bukutamu/kunjungan v1.2.0', $doc->body['pageTitle']);
        self::assertStringContainsString('<h3>kunjungan — Kunjungan Tamu</h3>', $doc->body['manifestHtml']);
        self::assertStringNotContainsString('"skema"', $doc->body['manifestHtml']);
        self::assertContains('manifest.html', $doc->content);
        self::assertArrayNotHasKey('manifestTautan', $doc->body, 'tanpa tombol navigasi');
        self::assertStringNotContainsString('<h1>', $doc->body['manifestHtml'], 'judul hanya sekali: H1 dokumen diangkat ke pageTitle');
        self::assertStringNotContainsString('Manifest API — bukutamu/kunjungan', $doc->body['manifestHtml']);
        $tpl = (string) file_get_contents(__DIR__ . '/../../../core/template/general/manifest.html');
        self::assertStringNotContainsString('btn', $tpl);
        self::assertDoesNotMatchRegularExpression('/<h[1-6][\s>]/', $tpl, 'template tanpa judul sendiri');

        $GLOBALS['doc'] = new \Gov2lib\document();
        Endpoint::sajikan($this->controller('kunjungan'), ['cmd' => 'readme']);
        self::assertSame('Kunjungan Tamu — bukutamu/kunjungan', $GLOBALS['doc']->body['pageTitle'], 'akar /readme = readme, judul = H1 readme');
        self::assertStringNotContainsString('<h1>', $GLOBALS['doc']->body['manifestHtml']);
    }

    public function testDispatcherMemanggilEndpointHanyaBilaControllerTakPunyaMethod(): void
    {
        $src = (string) file_get_contents(__DIR__ . '/../../../core/init/index.php');
        $i = strpos($src, 'method_exists(${$pageID},$cmd)');
        $j = strpos($src, 'Gov2lib\\Manifest\\Endpoint::sajikan');
        self::assertNotFalse($i);
        self::assertNotFalse($j);
        self::assertStringContainsString('in_array($cmd, Gov2lib\\Manifest\\Endpoint::CMDS, true)', $src, 'readme + alias manifest');
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
        self::assertSame('/bukutamu/kunjungan/readme/manifest', $r['mvc']['kunjungan']['url']['manifest']);
        self::assertSame('/bukutamu/kunjungan/readme', $r['mvc']['kunjungan']['url']['readme']);
        self::assertSame('/bukutamu/{mvc}/readme[/manifest|/release|/spec][/raw]', $r['url_pola']);
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
        self::assertStringContainsString('/bukutamu/kunjungan/readme', $html);
        self::assertStringContainsString('/bukutamu/kunjungan/readme/spec', $html);
        self::assertStringContainsString('tanpadok', $html);
        self::assertSame('README — bukutamu', $GLOBALS['doc']->body['pageTitle']);
        self::assertSame(['takeAll:components', 'take:bukutamu/index/dependencies'], $GLOBALS['self']->diambil);
    }

    public function testRuteIndeksReadmeDanAliasManifest(): void
    {
        $xml = (string) file_get_contents(__DIR__ . '/../../../core/config/route.xml');
        foreach (['/{app}/readme', '/{app}/readme/{cmd}[/{id}]', '/{app}/manifest', '/{app}/manifest/{cmd}[/{id}]'] as $uri) {
            self::assertStringContainsString("<uri>{$uri}</uri>", $xml);
        }
    }
}
