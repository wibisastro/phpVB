<?php

namespace Gov2lib\Database;

/**
 * Penolakan terkendali penjaga SQL baca-saja — kode mesin + pesan untuk agen.
 */
final class ReadOnlySqlException extends \InvalidArgumentException
{
    public function __construct(public readonly string $kode, string $pesan)
    {
        parent::__construct("{$kode}: {$pesan}");
    }

    public function pesan(): string
    {
        return substr($this->getMessage(), strlen($this->kode) + 2);
    }
}
