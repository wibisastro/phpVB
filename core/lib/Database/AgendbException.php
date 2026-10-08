<?php

namespace Gov2lib\Database;

/**
 * Penolakan terkendali endpoint agen DB (Gov2lib\agendb): kode mesin, status HTTP, data tambahan untuk agen.
 */
final class AgendbException extends \RuntimeException
{
    public function __construct(
        public readonly string $kode,
        string $pesan,
        public readonly int $status = 422,
        public readonly array $extra = [],
    ) {
        parent::__construct($pesan);
    }
}
