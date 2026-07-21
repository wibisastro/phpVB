<?php

namespace Tests\Unit\Lib;

use Gov2lib\Enums\PagePrivilege;
use Gov2lib\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class PagePrivilegeEnumTest extends TestCase
{
    public function test_closed_requires_owner_level(): void
    {
        $this->assertSame(UserRole::OWNER->level(), PagePrivilege::CLOSED->level());
    }

    public function test_maintenance_has_no_level(): void
    {
        $this->assertNull(PagePrivilege::MAINTENANCE->level());
    }

    public function test_default_map_contains_all_canonical_roles_and_closed(): void
    {
        $map = PagePrivilege::defaultMap();

        $this->assertSame([
            'public' => 0,
            'guest' => 1,
            'member' => 2,
            'admin' => 3,
            'webmaster' => 4,
            'owner' => 5,
            'developer' => 6,
            'closed' => 5,
        ], $map);
    }

    public function test_default_map_excludes_maintenance(): void
    {
        // maintenance TANPA level: dikecualikan dari fail-closed dan
        // ditangani cabang khusus gate — tak boleh muncul di peta.
        $this->assertArrayNotHasKey('maintenance', PagePrivilege::defaultMap());
    }

    public function test_role_names_are_not_page_privileges(): void
    {
        // 'closed'/'maintenance' bukan role user; sebaliknya nama role tak
        // boleh nyasar jadi case PagePrivilege.
        $this->assertNull(PagePrivilege::tryFrom('owner'));
        $this->assertNull(PagePrivilege::tryFrom('webmaster'));
        $this->assertNull(UserRole::tryFrom('closed'));
        $this->assertNull(UserRole::tryFrom('maintenance'));
    }
}
