<?php

namespace Gov2lib\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';

    /**
     * Check if the user can access the system.
     */
    public function canAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Get user-friendly message for blocked statuses.
     */
    public function blockMessage(): string
    {
        return match ($this) {
            self::PENDING => 'Akun Anda belum aktif, silahkan aktivasi terlebih dahulu',
            self::SUSPENDED => 'Akun Anda terblokir, silakan hubungi Admin',
            self::INACTIVE => 'Akun Anda tidak aktif, silakan hubungi Admin',
            self::ACTIVE => '',
        };
    }
}
