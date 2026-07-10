<?php

namespace Gov2lib\Exceptions;

/**
 * Error dari Supabase/PostgREST (fase T3 #6085).
 *
 * Membungkus respons error PostgREST (HTTP 4xx/5xx) menjadi exception phpVB
 * yang konsisten dengan exceptionHandler. Status HTTP asli dan kode error
 * PostgreSQL (mis. 42P01 tabel tak ada, 42501 RLS menolak) tersedia untuk
 * pemeriksaan programatik.
 */
class SupabaseException extends DatabaseException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly string $pgCode = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $previous);
    }
}
