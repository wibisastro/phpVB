<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\dsnSource;
use PHPUnit\Framework\TestCase;

/**
 * Kredensial meekro aktif di dsnSource (#10801): identitas koneksi,
 * pemasangan statik \DB, dan pemulihan setelah detour ke DSN lain
 * (gov2session::memberRead()/memberUpdateCounter() → `master`).
 *
 * Tidak membuka koneksi MySQL: applyMeekroCredential() hanya memutus
 * koneksi bila $internal_mysql sudah berupa mysqli, dan di sini tidak pernah ada.
 */
class DsnSourceCredentialTest extends TestCase
{
    /** @return array{user: string, pass: string, host: string, port: string, db: string} */
    private function cred(string $db, string $user = 'app', string $host = '127.0.0.1', string $port = '3306'): array
    {
        return ['user' => $user, 'pass' => 'x', 'host' => $host, 'port' => $port, 'db' => $db];
    }

    public function testCredentialKeyMengabaikanPasswordDanHurufBesarHost(): void
    {
        $a = $this->cred('kab_sdi', 'app', '127.0.0.1');
        $b = ['user' => 'app', 'pass' => 'beda', 'host' => ' 127.0.0.1 ', 'port' => '', 'db' => 'kab_sdi'];

        $this->assertSame(dsnSource::credentialKey($a), dsnSource::credentialKey($b));
        $this->assertSame('127.0.0.1|3306|app|kab_sdi', dsnSource::credentialKey($a));
    }

    public function testCredentialKeyBerbedaBilaDbAtauUserBerbeda(): void
    {
        $base = $this->cred('kab_sdi');

        $this->assertNotSame(dsnSource::credentialKey($base), dsnSource::credentialKey($this->cred('gov3_central')));
        $this->assertNotSame(dsnSource::credentialKey($base), dsnSource::credentialKey($this->cred('kab_sdi', 'gov3_definer')));
    }

    public function testApplyMemasangStatikDbDanMencatatKredensialAktif(): void
    {
        $domain = $this->cred('kab_sdi_sumenepkab', 'kab_sdi');
        dsnSource::applyMeekroCredential($domain);

        $this->assertSame('kab_sdi_sumenepkab', \DB::$dbName);
        $this->assertSame('kab_sdi', \DB::$user);
        $this->assertSame($domain, dsnSource::activeCredential());
    }

    public function testRestoreMengembalikanDsnDomainSetelahDetourMaster(): void
    {
        $domain = $this->cred('kab_sdi_sumenepkab', 'kab_sdi');
        $master = $this->cred('gov3_central', 'gov3_definer');

        dsnSource::applyMeekroCredential($domain);
        $prev = dsnSource::activeCredential();

        dsnSource::applyMeekroCredential($master);
        $this->assertSame('gov3_central', \DB::$dbName);

        dsnSource::restoreCredential($prev);
        $this->assertSame('kab_sdi_sumenepkab', \DB::$dbName);
        $this->assertSame('kab_sdi', \DB::$user);
    }

    public function testRestoreNullTidakMengubahApapun(): void
    {
        $master = $this->cred('gov3_central', 'gov3_definer');
        dsnSource::applyMeekroCredential($master);

        dsnSource::restoreCredential(null);

        $this->assertSame('gov3_central', \DB::$dbName);
        $this->assertSame($master, dsnSource::activeCredential());
    }
}
