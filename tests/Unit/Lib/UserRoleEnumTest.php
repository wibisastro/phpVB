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
    }

    public function test_has_privilege_returns_true_for_higher_role(): void
    {
        $this->assertTrue(UserRole::ADMIN->hasPrivilege(UserRole::MEMBER));
        $this->assertTrue(UserRole::WEBMASTER->hasPrivilege(UserRole::ADMIN));
        $this->assertTrue(UserRole::MEMBER->hasPrivilege(UserRole::MEMBER));
    }

    public function test_has_privilege_returns_false_for_lower_role(): void
    {
        $this->assertFalse(UserRole::GUEST->hasPrivilege(UserRole::MEMBER));
        $this->assertFalse(UserRole::MEMBER->hasPrivilege(UserRole::ADMIN));
    }

    public function test_from_legacy_handles_case_insensitive(): void
    {
        $this->assertSame(UserRole::ADMIN, UserRole::fromLegacy('Admin'));
        $this->assertSame(UserRole::ADMIN, UserRole::fromLegacy('admin'));
        $this->assertSame(UserRole::ADMIN, UserRole::fromLegacy(' admin '));
    }

    public function test_from_legacy_returns_public_for_unknown(): void
    {
        $this->assertSame(UserRole::PUBLIC, UserRole::fromLegacy('unknown'));
        $this->assertSame(UserRole::PUBLIC, UserRole::fromLegacy(''));
    }

    public function test_to_legacy_array_returns_correct_structure(): void
    {
        $array = UserRole::toLegacyArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('admin', $array);
        $this->assertArrayHasKey('member', $array);
        $this->assertArrayHasKey('guest', $array);
        $this->assertEquals(4, $array['admin']);
        $this->assertEquals(3, $array['member']);
    }
}
