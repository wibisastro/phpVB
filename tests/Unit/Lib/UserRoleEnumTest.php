<?php

namespace Tests\Unit\Lib;

use Gov2lib\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleEnumTest extends TestCase
{
    public function test_role_levels_are_ordered(): void
    {
        $this->assertLessThan(UserRole::GUEST->level(), UserRole::PUBLIC->level());
        $this->assertLessThan(UserRole::MEMBER->level(), UserRole::GUEST->level());
        $this->assertLessThan(UserRole::ADMIN->level(), UserRole::MEMBER->level());
        $this->assertLessThan(UserRole::WEBMASTER->level(), UserRole::ADMIN->level());
        $this->assertLessThan(UserRole::OWNER->level(), UserRole::WEBMASTER->level());
        $this->assertLessThan(UserRole::DEVELOPER->level(), UserRole::OWNER->level());
    }

    public function test_canonical_levels_match_r1_taxonomy(): void
    {
        // Kanonik R1 role-framework: public=0 < guest=1 < member=2 < admin=3
        // < webmaster=4 < owner=5 < developer=6. Angka ini KONTRAK gate —
        // identik dgn posisi enum DB member.sql utk guest..developer.
        $this->assertSame(0, UserRole::PUBLIC->level());
        $this->assertSame(1, UserRole::GUEST->level());
        $this->assertSame(2, UserRole::MEMBER->level());
        $this->assertSame(3, UserRole::ADMIN->level());
        $this->assertSame(4, UserRole::WEBMASTER->level());
        $this->assertSame(5, UserRole::OWNER->level());
        $this->assertSame(6, UserRole::DEVELOPER->level());
    }

    public function test_has_privilege_returns_true_for_higher_role(): void
    {
        $this->assertTrue(UserRole::ADMIN->hasPrivilege(UserRole::MEMBER));
        $this->assertTrue(UserRole::WEBMASTER->hasPrivilege(UserRole::ADMIN));
        $this->assertTrue(UserRole::MEMBER->hasPrivilege(UserRole::MEMBER));
        $this->assertTrue(UserRole::DEVELOPER->hasPrivilege(UserRole::OWNER));
    }

    public function test_has_privilege_returns_false_for_lower_role(): void
    {
        $this->assertFalse(UserRole::GUEST->hasPrivilege(UserRole::MEMBER));
        $this->assertFalse(UserRole::MEMBER->hasPrivilege(UserRole::ADMIN));
        $this->assertFalse(UserRole::OWNER->hasPrivilege(UserRole::DEVELOPER));
    }

    public function test_from_name_handles_case_and_whitespace(): void
    {
        $this->assertSame(UserRole::ADMIN, UserRole::fromName('Admin'));
        $this->assertSame(UserRole::ADMIN, UserRole::fromName('admin'));
        $this->assertSame(UserRole::ADMIN, UserRole::fromName(' admin '));
        $this->assertSame(UserRole::DEVELOPER, UserRole::fromName('developer'));
        $this->assertSame(UserRole::PUBLIC, UserRole::fromName('public'));
    }

    public function test_from_name_falls_back_to_guest_for_unknown(): void
    {
        // Prinsip R1: framework toleran role asing (walidata, komisioner,
        // data lama) — lantai guest + log, BUKAN fatal dan BUKAN public.
        $this->assertSame(UserRole::GUEST, UserRole::fromName('walidata'));
        $this->assertSame(UserRole::GUEST, UserRole::fromName(''));
        $this->assertSame(UserRole::GUEST, UserRole::fromName('closed'));
    }

    public function test_to_legacy_array_returns_canonical_structure(): void
    {
        $array = UserRole::toLegacyArray();

        $this->assertSame([
            'public' => 0,
            'guest' => 1,
            'member' => 2,
            'admin' => 3,
            'webmaster' => 4,
            'owner' => 5,
            'developer' => 6,
        ], $array);
    }
}
