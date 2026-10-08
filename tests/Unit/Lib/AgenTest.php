<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Gov2lib\agen;
use Gov2lib\agenHandler;
use Gov2lib\gov2session;
use PHPUnit\Framework\TestCase;

/** Handler tiruan: tanpa mysqli — hanya cariMember yang dipakai controller. */
class AgenFakeHandler extends agenHandler
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $member = [];
    public array $panggilan = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function cariMember(string $email): array
    {
        $this->panggilan[] = ['cariMember', $email];

        return $this->member[strtolower($email)] ?? [];
    }
}

/** Sesi tiruan: superuser.xml tidak dibaca dari disk. */
class AgenFakeSession extends gov2session
{
    public string|bool $su = false;

    public function checkSuperuser(?string $newPageID = null): string|bool
    {
        return $this->su;
    }
}

class AgenTest extends TestCase
{
    private const SECRET = 'rahasia-uji-agen';
    private const KEY = 'kunci-jwt-uji-0123456789abcdef';
    private string $logFile;
    private array $prevServer;

    protected function setUp(): void
    {
        $this->prevServer = $_SERVER;
        $GLOBALS['pageID'] = 'bukutamu';
        $GLOBALS['publickey'] = self::KEY;
        $GLOBALS['config'] = new \SimpleXMLElement('<config><domain/></config>');
        $GLOBALS['config']->domain->attr['dsn'] = 'becik.uji';
        $GLOBALS['doc'] = new \Gov2lib\document();
        $this->logFile = tempnam(sys_get_temp_dir(), 'agen-audit-');
        ini_set('error_log', $this->logFile);
        putenv('PHPVB_AGEN_SECRET=' . self::SECRET);
        unset($_SERVER[agen::H_SECRET], $_SERVER['HTTP_X_GOV3_USER'], $_SERVER['HTTP_X_GOV3_SESSION']);
        $_SERVER['SERVER_NAME'] = 'becik.uji';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        http_response_code(200);

        $h = new AgenFakeHandler();
        $h->ses = new AgenFakeSession();
        $h->member = [
            'fathur@contoh.id' => [['id' => 5, 'account_id' => 9, 'fullname' => 'Fathur', 'email' => 'fathur@contoh.id', 'role' => 'member', 'status' => 'active']],
            'wibi@contoh.id' => [['id' => 1, 'account_id' => 4, 'fullname' => 'Wibi', 'email' => 'wibi@contoh.id', 'role' => 'webmaster', 'status' => 'active']],
            'ganda@contoh.id' => [['id' => 2, 'account_id' => 5, 'role' => 'member', 'status' => 'active', 'email' => 'ganda@contoh.id'],
                ['id' => 3, 'account_id' => 6, 'role' => 'member', 'status' => 'active', 'email' => 'ganda@contoh.id']],
            'blokir@contoh.id' => [['id' => 9, 'account_id' => 78, 'fullname' => 'B', 'email' => 'blokir@contoh.id', 'role' => 'member', 'status' => 'suspended']],
            'baru@contoh.id' => [['id' => 10, 'account_id' => 79, 'fullname' => 'N', 'email' => 'baru@contoh.id', 'role' => 'member', 'status' => 'pending']],
        ];
        $GLOBALS['self'] = $h;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->prevServer;
        putenv('PHPVB_AGEN_SECRET');
        ini_restore('error_log');
        @unlink($this->logFile);
        unset($GLOBALS['self'], $GLOBALS['pageID'], $GLOBALS['config'], $GLOBALS['doc'], $GLOBALS['publickey']);
    }

    private function handler(): AgenFakeHandler
    {
        return $GLOBALS['self'];
    }

    private function sebagai(string $email, bool $secret = true, string $sesi = 'ses_uji'): void
    {
        if ($secret) {
            $_SERVER[agen::H_SECRET] = self::SECRET;
        }
        $_SERVER['HTTP_X_GOV3_USER'] = $email;
        if ($sesi !== '') {
            $_SERVER['HTTP_X_GOV3_SESSION'] = $sesi;
        }
    }

    private function audit(): array
    {
        $json = [];
        foreach (array_filter(array_map('trim', file($this->logFile) ?: [])) as $b) {
            $pos = strpos($b, '{');
            $d = $pos === false ? null : json_decode(substr($b, $pos), true);
            if (is_array($d) && ($d['ev'] ?? '') === 'phpvb.agen') {
                $json[] = $d;
            }
        }

        return $json;
    }

    public function testPingTanpaIdentitasTidakBocorkanRahasia(): void
    {
        $r = (new agen())->ping([]);
        self::assertTrue($r['ok'] && $r['secret_configured']);
        self::assertSame(['ping', 'sesi', 'siapa'], $r['cmd']);
        self::assertStringNotContainsString(self::SECRET, json_encode($r));
        putenv('PHPVB_AGEN_SECRET');
        self::assertFalse((new agen())->ping([])['secret_configured']);
    }

    public function testMatiTanpaRahasiaServer(): void
    {
        putenv('PHPVB_AGEN_SECRET');
        $this->sebagai('wibi@contoh.id');
        $r = (new agen())->sesi(['cmd' => 'sesi']);
        self::assertSame(503, http_response_code());
        self::assertSame('AgenDisabled', $r['error']);
        self::assertSame([], $this->handler()->panggilan);
    }

    public function testRahasiaSalahDanGetDitolak(): void
    {
        $this->sebagai('wibi@contoh.id');
        $_SERVER[agen::H_SECRET] = 'salah';
        $r = (new agen())->sesi([]);
        self::assertSame(403, http_response_code());
        self::assertSame('BadSecret', $r['error']);
        self::assertSame([], $this->handler()->panggilan, 'tidak menyentuh DB sebelum rahasia cocok');

        $_SERVER[agen::H_SECRET] = self::SECRET;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $r = (new agen())->sesi([]);
        self::assertSame(405, http_response_code());
        self::assertSame('MethodNotAllowed', $r['error']);
    }

    public function testIdentitasDanSesiKancilWajib(): void
    {
        $_SERVER[agen::H_SECRET] = self::SECRET;
        $r = (new agen())->sesi([]);
        self::assertSame('BLOCKED_NO_IDENTITY', $r['error']);
        $this->sebagai('wibi@contoh.id', true, '');
        $r = (new agen())->sesi([]);
        self::assertSame(422, http_response_code());
        self::assertSame('SessionRequired', $r['error']);
        self::assertSame([], $this->handler()->panggilan);
    }

    public function testMemberTakAdaGandaSuspendedPending(): void
    {
        foreach (['tidakada@contoh.id' => [404, 'NoAccount'], 'ganda@contoh.id' => [409, 'AmbiguousAccount'],
            'blokir@contoh.id' => [403, 'Suspended'], 'baru@contoh.id' => [403, 'Pending']] as $email => [$status, $kode]) {
            $this->sebagai($email);
            $r = (new agen())->sesi([]);
            self::assertSame($status, http_response_code(), $email);
            self::assertSame($kode, $r['error'], $email);
            self::assertArrayNotHasKey('token', $r);
        }
        self::assertSame(4, count($this->audit()));
        self::assertSame(['NoAccount', 'AmbiguousAccount', 'Suspended', 'Pending'], array_column($this->audit(), 'hasil'));
    }

    public function testSesiDiterbitkanDenganKlaimLoginDanAuditTanpaToken(): void
    {
        $this->handler()->ses->su = 'webmaster';
        $this->sebagai('Fathur@contoh.id');
        $r = (new agen())->sesi(['cmd' => 'sesi']);
        self::assertTrue($r['ok'], json_encode($r));
        self::assertSame('Gov2Session', $r['cookie']);
        self::assertSame(['id' => 5, 'account_id' => 9, 'fullname' => 'Fathur', 'email' => 'fathur@contoh.id', 'role' => 'member', 'status' => 'active'], $r['member']);
        self::assertSame('webmaster', $r['superuser']);
        self::assertSame(agen::TTL_BAWAAN, $r['ttl']);

        $claims = (array) JWT::decode($r['token'], new Key(self::KEY, 'HS256'));
        self::assertSame(9, $claims['account_id']);
        self::assertSame(5, $claims['id']);
        self::assertSame('member', $claims['userRole']);
        self::assertSame('bukutamu', $claims['pageID']);
        self::assertSame('phpvb', $claims['agen']);
        self::assertSame('ses_uji', $claims['agen_session']);
        self::assertSame($claims['iat'] + agen::TTL_BAWAAN, $claims['exp']);
        self::assertSame($claims['exp'], $r['exp']);
        self::assertSame(hash_hmac('sha256', 'gov2csrf.v2|9', self::KEY), $r['csrf']);
        self::assertSame([['cariMember', 'fathur@contoh.id']], $this->handler()->panggilan);

        $log = $this->audit();
        self::assertCount(1, $log);
        self::assertSame(['sesi', 'ok', 'fathur@contoh.id', 5, 'member', 'webmaster', 'ses_uji', agen::TTL_BAWAAN],
            [$log[0]['cmd'], $log[0]['hasil'], $log[0]['email'], $log[0]['uid'], $log[0]['role'], $log[0]['superuser'], $log[0]['session'], $log[0]['ttl']]);
        self::assertStringNotContainsString($r['token'], (string) file_get_contents($this->logFile));
    }

    public function testTtlDibatasi24Jam(): void
    {
        $this->sebagai('wibi@contoh.id');
        $r = (new agen())->sesi(['ttl' => 99 * 3600]);
        self::assertSame(agen::TTL_MAKS, $r['ttl']);
        $r = (new agen())->sesi(['ttl' => 600]);
        self::assertSame(600, $r['ttl']);
    }

    public function testSiapaMembacaCookieBukanRahasia(): void
    {
        $r = (new agen())->siapa([]);
        self::assertSame(401, http_response_code());
        self::assertSame('NotLogin', $r['error']);

        $this->handler()->ses->val = ['account_id' => 9, 'id' => 5, 'fullname' => 'Fathur', 'email' => 'fathur@contoh.id', 'userRole' => 'member', 'status' => 'active',
            'opd_id' => 12, 'opd' => 'Diskominfo', 'wilayah_id' => '6402', 'exp' => 1234, 'agen' => 'phpvb', 'pageID' => 'bukutamu', 'rahasia_lain' => 'x'];
        $this->handler()->ses->su = 'admin';
        $r = (new agen())->siapa([]);
        self::assertTrue($r['ok']);
        self::assertSame(['id' => 5, 'account_id' => 9, 'fullname' => 'Fathur', 'email' => 'fathur@contoh.id', 'role' => 'member', 'status' => 'active'], $r['member']);
        self::assertSame('admin', $r['superuser']);
        self::assertSame('admin', $r['peran_efektif']);
        self::assertSame(['opd_id' => 12, 'opd' => 'Diskominfo', 'wilayah_id' => '6402'], $r['konteks']);
        self::assertSame('phpvb', $r['agen']);
        self::assertArrayNotHasKey('rahasia_lain', $r);
    }
}
