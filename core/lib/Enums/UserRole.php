<?php

namespace Gov2lib\Enums;

enum UserRole: string
{
    case PUBLIC = 'public';
    case GUEST = 'guest';
    case PIMPINAN = 'pimpinan';
    case MEMBER = 'member';
    case ADMIN = 'admin';
    case WEBMASTER = 'webmaster';
    case OWNER = 'owner';

    /**
     * Get the numeric privilege level for role comparison.
     */
    public function level(): int
    {
        return match ($this) {
            self::PUBLIC => 0,
            self::GUEST => 1,
            self::PIMPINAN => 2,
            self::MEMBER => 3,
            self::ADMIN => 4,
            self::WEBMASTER => 5,
            self::OWNER => 6,
        };
    }

    /**
     * Check if this role has at least the given privilege level.
     */
    public function hasPrivilege(self $required): bool
    {
        return $this->level() >= $required->level();
    }

    /**
     * Get a UserRole from a legacy role level array.
     */
    public static function fromLegacy(string $role): self
    {
        return self::tryFrom(strtolower(trim($role))) ?? self::PUBLIC;
    }

    /**
     * Get all roles as an associative array [name => level].
     */
    public static function toLegacyArray(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->level();
        }
        return $result;
    }
}
