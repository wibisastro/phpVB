<?php

declare(strict_types=1);

namespace Gov2lib\Contracts;

/**
 * Adapter import payload eksternal → rows domain target — #6134 slice D.
 *
 * Satu adapter per domain tujuan: options (pertama), menyusul wilayah dan
 * instansi (tiket terpisah). Adapter MURNI (validasi + mapping, tanpa I/O)
 * — persistensi tetap urusan model domain masing-masing, supaya adapter
 * bisa diuji tanpa DB dan dipakai ulang dari sumber payload mana pun
 * (tools/call gurita, file, dsb).
 */
interface ImportAdapterInterface
{
    /**
     * Validasi payload (untrusted input — keputusan 9 #6134).
     *
     * @param array $payload hasil decode payload kanonik
     * @return array<int, string> daftar pesan error; kosong = valid
     */
    public function validate(array $payload): array;

    /**
     * Mapping payload valid → rows domain target ber-id sintetis berurutan
     * (persister me-remap id nyata saat INSERT). Panggil hanya setelah
     * validate() mengembalikan kosong.
     *
     * @param array $payload payload yang sudah lolos validate()
     * @param array $context konteks import (mis. app tujuan, connection_id)
     * @return array<int, array<string, mixed>>
     */
    public function toRows(array $payload, array $context = []): array;
}
