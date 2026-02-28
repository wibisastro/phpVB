<?php

namespace Tests\Unit\Lib;

use Gov2lib\Enums\UserStatus;
use PHPUnit\Framework\TestCase;

class UserStatusEnumTest extends TestCase
{
    public function test_active_can_access(): void
    {
        $this->assertTrue(UserStatus::ACTIVE->canAccess());
    }

    public function test_non_active_cannot_access(): void
    {
        $this->assertFalse(UserStatus::PENDING->canAccess());
        $this->assertFalse(UserStatus::SUSPENDED->canAccess());
        $this->assertFalse(UserStatus::INACTIVE->canAccess());
    }

    public function test_block_message_returns_empty_for_active(): void
    {
        $this->assertEmpty(UserStatus::ACTIVE->blockMessage());
    }

    public function test_block_message_returns_message_for_pending(): void
    {
        $message = UserStatus::PENDING->blockMessage();

        $this->assertNotEmpty($message);
        $this->assertStringContainsString('aktivasi', $message);
    }

    public function test_status_from_string(): void
    {
        $this->assertSame(UserStatus::ACTIVE, UserStatus::from('active'));
        $this->assertSame(UserStatus::PENDING, UserStatus::from('pending'));
        $this->assertSame(UserStatus::SUSPENDED, UserStatus::from('suspended'));
    }
}
