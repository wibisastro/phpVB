<?php

namespace Tests\Unit\Lib;

use Gov2lib\gov2session;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Gate matrix R1 role-framework: handleAuthorization() untuk 7 role kanonik
 * × 7 privilege kanonik, plus jalur override XML, privilege asing, role asing,
 * dan maintenance.
 *
 * Bisa diuji TANPA DB justru karena R1: level user dari enum UserRole, bukan
 * DESCRIBE member. Oracle level di test ini sengaja HARDCODE (bukan membaca
 * enum) supaya jadi kontrak independen: public=0 < guest=1 < member=2 <
 * admin=3 < webmaster=4 < owner=5 < developer=6; closed=5.
 */
class GateMatrixTest extends TestCase
{
    /** App fiktif: tak punya pageroles.xml/superuser.xml → murni peta kanonik. */
    private const APP = 'phpunit_gate_matrix_app';

    protected function setUp(): void
    {
        // Redam error_log (fromName fallback + deprecation warn) dari output test.
        ini_set('error_log', '/dev/null');
    }

    private function gate(string $userRole, string $privilege, string $app = self::APP, ?object $config = null, string $maintenance = ''): void
    {
        global $pageID;
        $pageID = $app; // checkSuperuser() membaca global, bukan parameter

        $config ??= simplexml_load_string('<config/>');
        $GLOBALS['config'] = $config;

        $ses = (new ReflectionClass(gov2session::class))->newInstanceWithoutConstructor();
        $ses->val = ['userRole' => $userRole, 'account_id' => -999];

        $method = new \ReflectionMethod(gov2session::class, 'handleAuthorization');
        $method->setAccessible(true);
        $method->invoke($ses, $app, new \stdClass(), $config, $privilege, $maintenance);
    }

    /** @return array<string, array{string, string, bool}> */
    public static function matrixProvider(): array
    {
        $roleLevels = ['public' => 0, 'guest' => 1, 'member' => 2, 'admin' => 3, 'webmaster' => 4, 'owner' => 5, 'developer' => 6];
        $privLevels = ['guest' => 1, 'member' => 2, 'admin' => 3, 'webmaster' => 4, 'owner' => 5, 'developer' => 6, 'closed' => 5];

        $cases = [];
        foreach ($roleLevels as $role => $rLevel) {
            foreach ($privLevels as $priv => $pLevel) {
                $cases["{$role} x {$priv}"] = [$role, $priv, $rLevel >= $pLevel];
            }
        }
        return $cases;
    }

    #[DataProvider('matrixProvider')]
    public function test_matrix_7_role_x_7_privilege(string $role, string $privilege, bool $expectAllow): void
    {
        if ($expectAllow) {
            $this->gate($role, $privilege);
            $this->addToAssertionCount(1); // lolos tanpa exception = ALLOW
            return;
        }

        try {
            $this->gate($role, $privilege);
            $this->fail("role {$role} seharusnya DITOLAK privilege {$privilege}");
        } catch (\Exception $e) {
            $expectedPrefix = $privilege === 'closed' ? 'Closed:' : 'Unauthorized:';
            $this->assertStringStartsWith($expectedPrefix, $e->getMessage());
        }
    }

    public function test_maintenance_always_denies_with_hour_in_message(): void
    {
        try {
            $this->gate('developer', 'maintenance', self::APP, null, '05.30');
            $this->fail('maintenance seharusnya selalu menolak');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Maintenance:', $e->getMessage());
            // Regresi ekstraksi 2026: $_maintenance dulu hilang di
            // handleAuthorization() → jam kosong. R1 meneruskannya lagi.
            $this->assertStringContainsString('hingga jam 05.30', $e->getMessage());
        }
    }

    public function test_unknown_privilege_fails_closed(): void
    {
        foreach (['sdi', 'walidata', 'default'] as $privilege) {
            try {
                $this->gate('developer', $privilege);
                $this->fail("privilege asing '{$privilege}' seharusnya fail-closed");
            } catch (\Exception $e) {
                $this->assertStringStartsWith('UnknownPrivilege:', $e->getMessage());
            }
        }
    }

    public function test_unknown_user_role_falls_back_to_guest(): void
    {
        // Toleransi role asing (prinsip R1): level efektif = guest.
        $this->gate('walidata', 'guest');
        $this->addToAssertionCount(1);

        try {
            $this->gate('walidata', 'member');
            $this->fail('role asing (efektif guest) seharusnya ditolak privilege member');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Unauthorized:', $e->getMessage());
        }
    }

    /**
     * Override per-app gov2login (fixture NYATA di apps/gov2login/xml/
     * pageroles.xml: member=3, admin=4, webmaster=5, default=0) — hasil
     * EFEKTIF-nya dipertahankan R1 (naik satu tingkat dari nama privilege).
     */
    public function test_gov2login_app_override_preserved(): void
    {
        // member=3 → role member ditolak, admin lolos
        try {
            $this->gate('member', 'member', 'gov2login');
            $this->fail('gov2login: member seharusnya ditolak privilege member (override 3)');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Unauthorized:', $e->getMessage());
        }
        $this->gate('admin', 'member', 'gov2login');

        // webmaster=5 → role webmaster ditolak, owner lolos
        try {
            $this->gate('webmaster', 'webmaster', 'gov2login');
            $this->fail('gov2login: webmaster seharusnya ditolak privilege webmaster (override 5)');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Unauthorized:', $e->getMessage());
        }
        $this->gate('owner', 'webmaster', 'gov2login');

        // closed TIDAK ada di XML gov2login → merge R0/R1 mempertahankan 5
        try {
            $this->gate('webmaster', 'closed', 'gov2login');
            $this->fail('gov2login: webmaster seharusnya ditolak privilege closed');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Closed:', $e->getMessage());
        }
        $this->gate('owner', 'closed', 'gov2login');

        // 'default'=0 khusus gov2login tetap dikenal (override menambah nama)
        $this->gate('guest', 'default', 'gov2login');
        $this->addToAssertionCount(1);
    }

    public function test_non_numeric_level_fails_closed(): void
    {
        // Typo config trusted (<webmaster>x</webmaster>) tak boleh membuka
        // halaman: (int)"x"=0 = fail-open. Guard is_numeric → UnknownPrivilege.
        $config = simplexml_load_string('<config><pageroles><webmaster>x</webmaster></pageroles></config>');

        foreach (['developer', 'webmaster', 'guest'] as $role) {
            try {
                $this->gate($role, 'webmaster', self::APP, $config);
                $this->fail("level non-numerik seharusnya fail-closed (role {$role})");
            } catch (\Exception $e) {
                $this->assertStringStartsWith('UnknownPrivilege:', $e->getMessage());
            }
        }

        // Privilege lain di config yang sama tetap normal (kanonik).
        $this->gate('admin', 'admin', self::APP, $config);
        $this->addToAssertionCount(1);
    }

    public function test_server_config_override_still_applies(): void
    {
        $config = simplexml_load_string('<config><pageroles><member>3</member></pageroles></config>');

        try {
            $this->gate('member', 'member', self::APP, $config);
            $this->fail('override config server member=3 seharusnya menolak role member');
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Unauthorized:', $e->getMessage());
        }

        $this->gate('admin', 'member', self::APP, $config);
        $this->addToAssertionCount(1);
    }
}
