<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Firebase\JWT\JWT;
use Gov2lib\context;
use Gov2lib\fileSource;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests untuk Gov2lib\context (konteks konten lintas-portal).
 *
 * Kasus:
 * - tanpa apa pun → no-op: active() false, path '', tenant = fileSource::tenant(), db ''
 * - load(array) konteks penuh → path/db/isForeign benar
 * - nilai jahat (path traversal, spasi, scope asing) → konteks kosong
 * - scope '' + slug portal sendiri → aktif tapi db '' dan tidak foreign
 * - set()/clear() menulis/menghapus 4 key sesi
 * - cookie JWT garbage → kosong tanpa exception; cookie JWT sah → termuat
 * - supervisor() membaca atribut domain config (SimpleXMLElement & stdClass)
 */
class ContextTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['config'] = new \SimpleXMLElement('<config/>');
        unset($_SERVER['SERVER_NAME'], $_COOKIE['Gov2Session'], $GLOBALS['publickey']);
        context::reset();
    }

    protected function tearDown(): void
    {
        context::reset();
        unset($GLOBALS['config'], $_SERVER['SERVER_NAME'], $_COOKIE['Gov2Session'], $GLOBALS['publickey']);
    }

    // (a) tanpa apa pun → identik dengan hari ini

    public function testTanpaKonteksAdalahNoOp(): void
    {
        $_SERVER['SERVER_NAME'] = 'banglikab.gov3.id';

        $this->assertFalse(context::active());
        $this->assertSame('', context::path());
        $this->assertSame('', context::db());
        $this->assertSame('', context::scope());
        $this->assertSame('', context::slug());
        $this->assertSame('', context::unit());
        $this->assertSame(0, context::unitId());
        $this->assertSame(fileSource::tenant(), context::tenant());
        $this->assertSame('banglikab', context::tenant());
        $this->assertFalse(context::isForeign());
        $this->assertFalse(context::supervisor());
    }

    public function testTanpaKonteksTanpaServerNameTenantKosong(): void
    {
        $this->assertFalse(context::active());
        $this->assertSame('', context::tenant());
        $this->assertFalse(context::isForeign());
    }

    // (b) konteks penuh portal lain

    public function testLoadArrayKonteksPortalLain(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load([
            'ctx_scope' => 'kl',
            'ctx_slug' => 'kemenkeu',
            'ctx_unit' => 'itjen',
            'ctx_unit_id' => 5,
        ]);

        $this->assertTrue(context::active());
        $this->assertSame('kl', context::scope());
        $this->assertSame('kemenkeu', context::slug());
        $this->assertSame('itjen', context::unit());
        $this->assertSame(5, context::unitId());
        $this->assertSame('kemenkeu/itjen', context::path());
        $this->assertSame('kemenkeu', context::tenant());
        $this->assertSame('kl_kemenkeu', context::db());
        $this->assertTrue(context::isForeign());
    }

    public function testLoadArrayTanpaUnitPathHanyaSlug(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load(['ctx_scope' => 'prov', 'ctx_slug' => 'jabarprov', 'ctx_unit' => '', 'ctx_unit_id' => 99]);

        $this->assertSame('jabarprov', context::path());
        $this->assertSame('prov_jabarprov', context::db());
        // unit_id tanpa unit tidak bermakna → 0
        $this->assertSame(0, context::unitId());
    }

    public function testLoadArrayMenormalkanHurufBesar(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load(['ctx_scope' => 'KL', 'ctx_slug' => ' Kemenkeu ', 'ctx_unit' => 'Itjen']);

        $this->assertSame('kemenkeu/itjen', context::path());
        $this->assertSame('kl_kemenkeu', context::db());
    }

    // (c) nilai jahat → kosong

    public function testSlugPathTraversalDitolak(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load(['ctx_scope' => 'kl', 'ctx_slug' => '../x', 'ctx_unit' => '']);

        $this->assertFalse(context::active());
        $this->assertSame('', context::path());
        $this->assertSame('', context::db());
        $this->assertSame('kemenpanrb', context::tenant());
    }

    public function testUnitDenganSpasiMenggugurkanSeluruhKonteks(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'A B', 'ctx_unit_id' => 5]);

        $this->assertFalse(context::active());
        $this->assertSame('', context::path());
        $this->assertSame('', context::unit());
        $this->assertSame(0, context::unitId());
    }

    public function testScopeAsingDitolak(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load(['ctx_scope' => 'xx', 'ctx_slug' => 'kemenkeu']);

        $this->assertFalse(context::active());
        $this->assertSame('', context::db());
    }

    public function testScopeKosongUntukPortalLainDitolak(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        // Tanpa scope, db() tidak bisa dibentuk → konektor reader tak punya DB; harus kosong
        context::load(['ctx_scope' => '', 'ctx_slug' => 'kemenkeu']);

        $this->assertFalse(context::active());
        $this->assertFalse(context::isForeign());
    }

    public function testNilaiNonSkalarDariCookieTamperedDitolakTanpaWarning(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load(['ctx_scope' => ['kl'], 'ctx_slug' => ['kemenkeu'], 'ctx_unit_id' => ['5']]);

        $this->assertFalse(context::active());
    }

    public function testSanitizeMenerimaKeyPolosDanKeySesi(): void
    {
        $expected = ['scope' => 'kab', 'slug' => 'banglikab', 'unit' => 'bappeda', 'unit_id' => 7];

        $this->assertSame($expected, context::sanitize(['scope' => 'kab', 'slug' => 'banglikab', 'unit' => 'bappeda', 'unit_id' => '7']));
        $this->assertSame($expected, context::sanitize(['ctx_scope' => 'kab', 'ctx_slug' => 'banglikab', 'ctx_unit' => 'bappeda', 'ctx_unit_id' => 7]));
        $this->assertSame(['scope' => '', 'slug' => '', 'unit' => '', 'unit_id' => 0], context::sanitize([]));
    }

    // (d) scope '' + slug portal sendiri (drill unit kerja sendiri)

    public function testDrillUnitPortalSendiri(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        context::load(['ctx_scope' => '', 'ctx_slug' => 'kemenpanrb', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 3]);

        $this->assertTrue(context::active());
        $this->assertSame('kemenpanrb/itjen', context::path());
        $this->assertSame('kemenpanrb', context::tenant());
        $this->assertSame('', context::db());
        $this->assertFalse(context::isForeign());
    }

    // (e) set()/clear()

    public function testSetMenulisEmpatKeySesi(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $val = ['account_id' => 42];

        context::set($val, 'kl', 'kemenkeu', 'itjen', 5);

        $this->assertSame(42, $val['account_id']);
        $this->assertSame('kl', $val['ctx_scope']);
        $this->assertSame('kemenkeu', $val['ctx_slug']);
        $this->assertSame('itjen', $val['ctx_unit']);
        $this->assertSame(5, $val['ctx_unit_id']);

        // set() tidak mengubah state statis; caller yang load($val) setelah sesSave
        $this->assertFalse(context::active());
        context::load($val);
        $this->assertSame('kemenkeu/itjen', context::path());
    }

    public function testSetScopeKosongHanyaUntukPortalSendiri(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';

        $val = [];
        context::set($val, '', 'kemenpanrb', 'setmen', 9);
        $this->assertSame('kemenpanrb', $val['ctx_slug']);
        $this->assertSame('setmen', $val['ctx_unit']);

        $val = ['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu'];
        context::set($val, '', 'kemenkeu', 'itjen', 5);
        foreach (context::KEYS as $key) {
            $this->assertArrayHasKey($key, $val);
            $this->assertNull($val[$key]);
        }
    }

    public function testSetNilaiTakSahMembersihkan(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $val = ['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 5];

        context::set($val, 'kl', '../x');

        foreach (context::KEYS as $key) {
            $this->assertNull($val[$key]);
        }
    }

    public function testClearMenulisNullBukanUnset(): void
    {
        $val = ['account_id' => 1, 'ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu', 'ctx_unit' => 'itjen', 'ctx_unit_id' => 5];

        context::clear($val);

        $this->assertSame(1, $val['account_id']);
        foreach (context::KEYS as $key) {
            // null (bukan unset) supaya sesSave() array_merge menimpa key lama di token
            $this->assertArrayHasKey($key, $val);
            $this->assertNull($val[$key]);
        }

        context::load($val);
        $this->assertFalse(context::active());
    }

    // (f) cookie JWT

    public function testCookieGarbageKosongTanpaException(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $_COOKIE['Gov2Session'] = 'garbage';
        $GLOBALS['publickey'] = 'x';

        context::load();

        $this->assertFalse(context::active());
        $this->assertSame('', context::path());
        $this->assertSame('kemenpanrb', context::tenant());
        // cookie tidak disentuh — reset cookie urusan gov2session
        $this->assertSame('garbage', $_COOKIE['Gov2Session']);
    }

    public function testCookieDenganKunciSalahKosong(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $_COOKIE['Gov2Session'] = JWT::encode(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu'], 'kunci-benar', 'HS256');
        $GLOBALS['publickey'] = 'kunci-salah';

        $this->assertFalse(context::active());
    }

    public function testCookieTanpaPublickeyKosong(): void
    {
        $_COOKIE['Gov2Session'] = JWT::encode(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu'], 'k', 'HS256');

        $this->assertFalse(context::active());
    }

    public function testCookieSahDimuatLazy(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $GLOBALS['publickey'] = 'kunci-uji';
        $_COOKIE['Gov2Session'] = JWT::encode([
            'account_id' => 42,
            'ctx_scope' => 'kota',
            'ctx_slug' => 'bandungkota',
            'ctx_unit' => 'bappeda',
            'ctx_unit_id' => '77',
        ], 'kunci-uji', 'HS256');

        // getter memuat cookie sendiri tanpa load() eksplisit
        $this->assertTrue(context::active());
        $this->assertSame('bandungkota/bappeda', context::path());
        $this->assertSame('kota_bandungkota', context::db());
        $this->assertSame(77, context::unitId());
        $this->assertTrue(context::isForeign());
    }

    public function testCookieSahTanpaKeyCtxAdalahNoOp(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        $GLOBALS['publickey'] = 'kunci-uji';
        $_COOKIE['Gov2Session'] = JWT::encode(['account_id' => 42, 'userRole' => 'admin'], 'kunci-uji', 'HS256');

        $this->assertFalse(context::active());
        $this->assertSame('kemenpanrb', context::tenant());
    }

    public function testResetMemaksaMuatUlang(): void
    {
        $_SERVER['SERVER_NAME'] = 'kemenpanrb.gov3.id';
        context::load(['ctx_scope' => 'kl', 'ctx_slug' => 'kemenkeu']);
        $this->assertTrue(context::active());

        context::reset();

        // tanpa cookie → muat ulang = kosong
        $this->assertFalse(context::active());
    }

    // (g) supervisor()

    public function testSupervisorDariAtributDomain(): void
    {
        // config/index.php menyalin atribut node domain ke <attr> di $config->domain
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain><attr supervisor="1" dsn="kemenpanrb.gov3.id"/></domain></config>');
        $this->assertTrue(context::supervisor());

        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain><attr supervisor="0"/></domain></config>');
        $this->assertFalse(context::supervisor());

        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain><attr dsn="banglikab.gov3.id"/></domain></config>');
        $this->assertFalse(context::supervisor());
    }

    public function testSupervisorDefensifSaatConfigError(): void
    {
        $GLOBALS['config'] = new \stdClass();
        $GLOBALS['config']->error = 'ConfigFileNotExist';

        $this->assertFalse(context::supervisor());

        $GLOBALS['config'] = null;
        $this->assertFalse(context::supervisor());
    }

    // (h) gerbang otorisasi perpindahan konteks

    public function testMaySwitchButuhLoginDanRoleSwitchRoles(): void
    {
        // anonim / belum login
        $this->assertFalse(context::maySwitch([]));
        $this->assertFalse(context::maySwitch(['userRole' => 'admin']));
        $this->assertFalse(context::maySwitch(['account_id' => '', 'userRole' => 'admin']));
        $this->assertFalse(context::maySwitch(['account_id' => null, 'userRole' => 'webmaster']));

        // sudah login tapi role di luar SWITCH_ROLES (member terikat unitnya, selaras `locked`)
        $this->assertFalse(context::maySwitch(['account_id' => '7', 'userRole' => 'member']));
        $this->assertFalse(context::maySwitch(['account_id' => '7', 'userRole' => 'public']));
        $this->assertFalse(context::maySwitch(['account_id' => '7', 'userRole' => '']));
        $this->assertFalse(context::maySwitch(['account_id' => '7']));

        // role berwenang (huruf besar & spasi dinormalkan); guest ikut (keputusan Wibi 2026-09-11)
        $this->assertTrue(context::maySwitch(['account_id' => '7', 'userRole' => 'guest']));
        $this->assertTrue(context::maySwitch(['account_id' => '7', 'userRole' => ' Guest ']));
        $this->assertTrue(context::maySwitch(['account_id' => '7', 'userRole' => 'admin']));
        $this->assertTrue(context::maySwitch(['account_id' => '7', 'userRole' => 'webmaster']));
        $this->assertTrue(context::maySwitch(['account_id' => '7', 'userRole' => 'owner']));
        $this->assertTrue(context::maySwitch(['account_id' => 7, 'userRole' => ' Admin ']));

        // nilai jahat dari cookie yang di-tamper
        $this->assertFalse(context::maySwitch(['account_id' => '7', 'userRole' => ['admin']]));
    }

    public function testSupervisorForButuhAtributDomainDanRole(): void
    {
        $admin = ['account_id' => '7', 'userRole' => 'admin'];
        $member = ['account_id' => '7', 'userRole' => 'member'];

        // portal biasa: tidak pernah supervisor, siapa pun penggunanya
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain><attr dsn="banglikab.gov3.id"/></domain></config>');
        $this->assertFalse(context::supervisorFor($admin));
        $this->assertFalse(context::supervisorFor($member));

        // portal supervisor: hanya role berwenang
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain><attr supervisor="1"/></domain></config>');
        $this->assertTrue(context::supervisorFor($admin));
        $this->assertFalse(context::supervisorFor($member));
        $this->assertFalse(context::supervisorFor([]));
    }
}
