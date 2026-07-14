<?php

namespace Gov2lib;

use Gov2lib\Contracts\ImportAdapterInterface;

/**
 * Adapter import payload kanonik gurita → rows options — #6134 slice D.
 *
 * Payload transport (skema kanonik note-3 #6134) berbentuk nested tanpa id:
 *   { "gov2options": "1.0",
 *     "publisher": { "nama", "gurita", "dataset", "version" },
 *     "target":    { "app", "cluster" },
 *     "clusters":  [ { "nama", "type", "privilege",
 *                      "items": [ { "nama", "type", "value", "keterangan" } ] } ] }
 *
 * Mapping: clusters[] → rows level 1; items[] → rows level 2 (parent_id
 * menunjuk cluster); id sintetis berurutan (selaras flattenOptionsXml).
 * Provenance publisher disalin ke kolom JSON `metadata` per row (§4.2).
 *
 * Data dari gurita = untrusted input (keputusan 9): type whitelist, size cap
 * per field mengikuti DDL options, cap jumlah rows; escaping tetap kewajiban
 * saat render (nilai disimpan apa adanya, sama seperti input admin).
 */
class optionsImportAdapter implements ImportAdapterInterface
{
    /** Type item yang boleh diimpor (keputusan 9 #6134) */
    public const ITEM_TYPES = ['text', 'textbox', 'radio', 'checkbox'];
    public const PRIVILEGES = ['admin', 'webmaster', 'member'];

    /** Cap jumlah rows hasil satu import (cluster + item) */
    public const MAX_ROWS = 500;

    /** Size cap per field — mirror DDL tabel options */
    private const LEN_NAMA = 128;
    private const LEN_VALUE = 255;
    private const LEN_KETERANGAN = 255;

    public function validate(array $payload): array
    {
        $errors = [];

        if (($payload['gov2options'] ?? null) !== gov2option::PINNED_VERSION) {
            $errors[] = 'Versi payload tidak dikenal — wajib gov2options "' . gov2option::PINNED_VERSION . '"';
        }

        $targetApp = $payload['target']['app'] ?? null;

        if ($targetApp !== null && !self::validApp((string) $targetApp)) {
            $errors[] = 'target.app mengandung karakter di luar [a-zA-Z0-9_-]';
        }

        $clusters = $payload['clusters'] ?? null;

        if (!is_array($clusters) || $clusters === []) {
            $errors[] = 'clusters wajib array non-kosong';

            return $errors;
        }

        $rowCount = 0;

        foreach (array_values($clusters) as $ci => $cluster) {
            $label = 'clusters[' . $ci . ']';

            if (!is_array($cluster)) {
                $errors[] = "{$label} bukan objek";
                continue;
            }

            $errors = array_merge($errors, self::checkNama($cluster, $label));
            $errors = array_merge($errors, self::checkPrivilege($cluster, $label));
            $rowCount++;

            $items = $cluster['items'] ?? [];

            if (!is_array($items)) {
                $errors[] = "{$label}.items bukan array";
                continue;
            }

            foreach (array_values($items) as $ii => $item) {
                $iLabel = "{$label}.items[{$ii}]";

                if (!is_array($item)) {
                    $errors[] = "{$iLabel} bukan objek";
                    continue;
                }

                $errors = array_merge($errors, self::checkNama($item, $iLabel));
                $errors = array_merge($errors, self::checkPrivilege($item, $iLabel));
                $rowCount++;

                $type = $item['type'] ?? 'text';

                if (!in_array($type, self::ITEM_TYPES, true)) {
                    $errors[] = "{$iLabel}.type harus salah satu: " . join(', ', self::ITEM_TYPES);
                }

                $value = $item['value'] ?? null;

                if (!($value === null || is_scalar($value))) {
                    $errors[] = "{$iLabel}.value harus skalar atau null";
                } elseif (strlen((string) $value) > self::LEN_VALUE) {
                    $errors[] = "{$iLabel}.value melebihi " . self::LEN_VALUE . ' karakter';
                }

                if (strlen((string) ($item['keterangan'] ?? '')) > self::LEN_KETERANGAN) {
                    $errors[] = "{$iLabel}.keterangan melebihi " . self::LEN_KETERANGAN . ' karakter';
                }
            }
        }

        if ($rowCount > self::MAX_ROWS) {
            $errors[] = "Payload {$rowCount} rows melebihi cap " . self::MAX_ROWS;
        }

        return $errors;
    }

    /**
     * Context yang dikenal: 'app' (override target.app), 'connection_id',
     * 'imported_at' (default waktu sekarang).
     */
    public function toRows(array $payload, array $context = []): array
    {
        $app = (string) ($context['app'] ?? '') ?: (string) ($payload['target']['app'] ?? 'home');

        $publisher = $payload['publisher'] ?? [];
        $metadata = [
            'publisher' => $publisher['nama'] ?? null,
            'gurita' => $publisher['gurita'] ?? null,
            'dataset' => $publisher['dataset'] ?? null,
            'version' => $publisher['version'] ?? null,
            'imported_at' => (string) ($context['imported_at'] ?? date('c')),
            'connection_id' => $context['connection_id'] ?? null,
        ];

        $rows = [];
        $id = 0;

        foreach ($payload['clusters'] as $cluster) {
            $clusterId = ++$id;
            $clusterPrivilege = self::privilege($cluster);

            $rows[] = [
                'id' => $clusterId,
                'parent_id' => 0,
                'app' => $app,
                'nama' => (string) $cluster['nama'],
                'type' => 'option',
                'privilege' => $clusterPrivilege,
                'status' => 'on',
                'value' => '',
                'level' => 1,
                'level_label' => 'cluster',
                'keterangan' => self::keterangan($cluster),
                'metadata' => $metadata,
            ];

            foreach ($cluster['items'] ?? [] as $item) {
                $value = $item['value'] ?? null;

                $rows[] = [
                    'id' => ++$id,
                    'parent_id' => $clusterId,
                    'app' => $app,
                    'nama' => (string) $item['nama'],
                    'type' => (string) ($item['type'] ?? 'text'),
                    'privilege' => self::privilege($item, $clusterPrivilege),
                    'status' => 'on',
                    'value' => $value === null ? null : (string) $value,
                    'level' => 2,
                    'level_label' => 'option',
                    'keterangan' => self::keterangan($item),
                    'metadata' => $metadata,
                ];
            }
        }

        return $rows;
    }

    private static function validApp(string $app): bool
    {
        return $app !== '' && !preg_match('/[^a-zA-Z0-9_-]/', $app);
    }

    /** @return array<int, string> */
    private static function checkNama(array $node, string $label): array
    {
        $nama = $node['nama'] ?? null;

        if (!is_string($nama) || trim($nama) === '') {
            return ["{$label}.nama wajib string non-kosong"];
        }

        if (strlen($nama) > self::LEN_NAMA) {
            return ["{$label}.nama melebihi " . self::LEN_NAMA . ' karakter'];
        }

        return [];
    }

    /** @return array<int, string> */
    private static function checkPrivilege(array $node, string $label): array
    {
        $privilege = $node['privilege'] ?? null;

        if ($privilege !== null && !in_array($privilege, self::PRIVILEGES, true)) {
            return ["{$label}.privilege harus salah satu: " . join(', ', self::PRIVILEGES)];
        }

        return [];
    }

    private static function privilege(array $node, string $default = 'admin'): string
    {
        return (string) ($node['privilege'] ?? '') ?: $default;
    }

    private static function keterangan(array $node): ?string
    {
        $keterangan = $node['keterangan'] ?? null;

        return $keterangan === null ? null : (string) $keterangan;
    }
}
