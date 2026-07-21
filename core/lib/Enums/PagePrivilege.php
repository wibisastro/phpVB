<?php

namespace Gov2lib\Enums;

/**
 * Privilege halaman NON-ROLE (R1 role-framework).
 *
 * Dua konsep dipisah sejak R1: ROLE = atribut user per-instansi (UserRole),
 * PRIVILEGE halaman = syarat akses yang diminta authenticate(). Nama role
 * kanonik sah sebagai privilege (levelnya = level role); dua nama di enum ini
 * adalah privilege murni yang BUKAN role user:
 *
 * - CLOSED      : "menu ditutup" — hanya owner/developer yang tembus
 *                 (level = OWNER). Pesan penolakannya beda (Closed, bukan
 *                 Unauthorized).
 * - MAINTENANCE : selalu menolak dengan pesan jam maintenance — tidak punya
 *                 level, tidak pernah dibandingkan.
 *
 * #---coded by claude (R1 role-framework, 21 Jul 2026)
 */
enum PagePrivilege: string
{
    case CLOSED = 'closed';
    case MAINTENANCE = 'maintenance';

    /**
     * Level minimum user untuk menembus privilege ini; null = tanpa level
     * (maintenance ditangani cabang khusus, bukan perbandingan).
     */
    public function level(): ?int
    {
        return match ($this) {
            self::CLOSED => UserRole::OWNER->level(),
            self::MAINTENANCE => null,
        };
    }

    /**
     * Peta privilege kanonik [nama => level] — basis merge gate.
     *
     * Gabungan seluruh role kanonik (public=0 .. developer=6, termasuk 'owner'
     * yang sebelum R1 tak pernah terdefinisi di pageroles mana pun) + privilege
     * ber-level dari enum ini. MAINTENANCE sengaja TIDAK masuk peta:
     * dikecualikan dari fail-closed dan ditangani cabang khusus gate.
     */
    public static function defaultMap(): array
    {
        $map = UserRole::toLegacyArray();
        $map[self::CLOSED->value] = self::CLOSED->level();
        return $map;
    }
}
