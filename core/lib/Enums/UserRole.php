<?php

namespace Gov2lib\Enums;

/**
 * Taksonomi role user KANONIK — SATU-SATUNYA sumber hierarki (R1 role-framework).
 *
 * Gate otorisasi (gov2session::handleAuthorization) membaca level dari enum ini;
 * urutan enum DB `member.role` dan angka pageroles.xml BERHENTI jadi load-bearing
 * sejak R1 (XML tinggal override sadar, deprecated). PUBLIC = pseudo-role untuk
 * pengunjung tanpa login — tidak pernah tersimpan di DB.
 *
 * #---coded by claude (baseline 28 Feb 2026; R1 role-framework 21 Jul 2026)
 */
enum UserRole: string
{
    case PUBLIC = 'public';
    case GUEST = 'guest';
    case MEMBER = 'member';
    case ADMIN = 'admin';
    case WEBMASTER = 'webmaster';
    case OWNER = 'owner';
    case DEVELOPER = 'developer';

    /**
     * Get the numeric privilege level for role comparison.
     */
    public function level(): int
    {
        return match ($this) {
            self::PUBLIC => 0,
            self::GUEST => 1,
            self::MEMBER => 2,
            self::ADMIN => 3,
            self::WEBMASTER => 4,
            self::OWNER => 5,
            self::DEVELOPER => 6,
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
     * Resolusi nama role → enum, TOLERAN terhadap nama asing.
     *
     * Role tak dikenal (data lama, superuser.xml menyimpang, role domain lain
     * spt 'walidata'/'komisioner' sebelum ada mapping R5) jatuh ke GUEST + log —
     * bukan fatal, dan bukan PUBLIC: pemanggil gate selalu user ber-sesi login,
     * lantai wajarnya guest (role yang sama dengan auto-insert member baru).
     */
    public static function fromName(string $role): self
    {
        $normalized = strtolower(trim($role));
        $resolved = self::tryFrom($normalized);
        // Nilai bisa berasal dari URL/config — bersihkan newline (anti log
        // injection) dan potong sebelum masuk log.
        $safe = substr(str_replace(["\r", "\n"], ' ', $role), 0, 64);
        if ($resolved === null) {
            error_log("UserRole::fromName: role '{$safe}' tak dikenal — fallback guest");
            return self::GUEST;
        }
        if ($normalized !== $role) {
            // Nama dikenal tapi beda case/spasi (mis. superuser.xml 'Webmaster')
            // — dulu lookup exact-match diam-diam gagal (level 0, tertolak
            // semua); kini dinormalisasi. Log supaya penyimpangan config
            // terlihat, bukan eskalasi senyap.
            error_log("UserRole::fromName: role '{$safe}' dinormalisasi ke '{$normalized}' — rapikan sumbernya (superuser.xml/data)");
        }
        return $resolved;
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
