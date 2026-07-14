<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2option;
use Gov2lib\optionsImportAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests optionsImportAdapter (#6134 slice D) — validasi payload kanonik
 * gurita (untrusted input: type whitelist, size cap, cap rows) dan mapping
 * clusters/items → rows options ber-id sintetis + provenance metadata.
 * Adapter murni tanpa I/O — tanpa mock.
 */
class OptionsImportAdapterTest extends TestCase
{
    private optionsImportAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new optionsImportAdapter();
    }

    /** Payload kanonik minimal yang valid (bentuk note-3 #6134) */
    private static function payload(array $overrides = []): array
    {
        return array_replace([
            'gov2options' => '1.0',
            'publisher' => ['nama' => 'Kemdikbud', 'gurita' => 'https://gurita.kemdikbud.test',
                'dataset' => 'proses-bisnis', 'version' => '2026.07'],
            'target' => ['app' => 'home', 'cluster' => 'Proses Bisnis'],
            'clusters' => [
                ['nama' => 'Proses Bisnis', 'type' => 'option', 'privilege' => 'admin',
                    'items' => [
                        ['nama' => 'PB-01 Perencanaan', 'type' => 'text', 'value' => 'aktif'],
                        ['nama' => 'PB-02 Anggaran', 'type' => 'checkbox', 'value' => null,
                            'keterangan' => 'centang bila dipakai'],
                    ]],
            ],
        ], $overrides);
    }

    // ---- validate -----------------------------------------------------------

    public function testValidPayloadPasses(): void
    {
        $this->assertEquals([], $this->adapter->validate(self::payload()));
    }

    public function testWrongVersionRejected(): void
    {
        $errors = $this->adapter->validate(self::payload(['gov2options' => '2.0']));

        $this->assertStringContainsString('Versi payload', $errors[0]);
    }

    public function testMissingOrEmptyClustersRejected(): void
    {
        $this->assertNotEmpty($this->adapter->validate(self::payload(['clusters' => []])));

        $payload = self::payload();
        unset($payload['clusters']);
        $this->assertNotEmpty($this->adapter->validate($payload));
    }

    public function testItemTypeOutsideWhitelistRejected(): void
    {
        $payload = self::payload();
        $payload['clusters'][0]['items'][0]['type'] = 'script'; // injeksi type render

        $errors = $this->adapter->validate($payload);

        $this->assertStringContainsString('type harus salah satu', join('; ', $errors));
    }

    public function testNamaMissingOrOversizeRejected(): void
    {
        $payload = self::payload();
        unset($payload['clusters'][0]['items'][0]['nama']);
        $this->assertStringContainsString('nama wajib', join('; ', $this->adapter->validate($payload)));

        $payload = self::payload();
        $payload['clusters'][0]['nama'] = str_repeat('x', 129); // DDL nama VARCHAR(128)
        $this->assertStringContainsString('melebihi 128', join('; ', $this->adapter->validate($payload)));
    }

    public function testValueOversizeAndNonScalarRejected(): void
    {
        $payload = self::payload();
        $payload['clusters'][0]['items'][0]['value'] = str_repeat('x', 256); // DDL value VARCHAR(255)
        $this->assertStringContainsString('value melebihi 255', join('; ', $this->adapter->validate($payload)));

        $payload = self::payload();
        $payload['clusters'][0]['items'][0]['value'] = ['array' => 'bukan skalar'];
        $this->assertStringContainsString('skalar atau null', join('; ', $this->adapter->validate($payload)));
    }

    public function testPrivilegeOutsideWhitelistRejected(): void
    {
        $payload = self::payload();
        $payload['clusters'][0]['privilege'] = 'root';

        $this->assertStringContainsString('privilege harus salah satu', join('; ', $this->adapter->validate($payload)));
    }

    public function testTargetAppCharsetRejected(): void
    {
        $errors = $this->adapter->validate(self::payload(['target' => ['app' => '../home']]));

        $this->assertStringContainsString('target.app', $errors[0]);
    }

    public function testRowCapEnforced(): void
    {
        $items = array_map(
            fn (int $i): array => ['nama' => "Item {$i}", 'type' => 'text', 'value' => (string) $i],
            range(1, optionsImportAdapter::MAX_ROWS)
        );
        $payload = self::payload(['clusters' => [['nama' => 'Besar', 'items' => $items]]]);

        $errors = $this->adapter->validate($payload);

        $this->assertStringContainsString('melebihi cap', join('; ', $errors));
    }

    // ---- toRows -------------------------------------------------------------

    public function testToRowsMapsClustersAndItemsWithSyntheticIds(): void
    {
        $rows = $this->adapter->toRows(self::payload(), [
            'connection_id' => 7,
            'imported_at' => '2026-07-14T10:00:00+07:00',
        ]);

        $this->assertCount(3, $rows);
        $this->assertEquals([1, 2, 3], array_column($rows, 'id'));
        $this->assertEquals([0, 1, 1], array_column($rows, 'parent_id'));
        $this->assertEquals([1, 2, 2], array_column($rows, 'level'));

        $cluster = $rows[0];
        $this->assertEquals('Proses Bisnis', $cluster['nama']);
        $this->assertEquals('option', $cluster['type']);
        $this->assertEquals('cluster', $cluster['level_label']);
        $this->assertEquals('home', $cluster['app'], 'default app = target.app');

        $item = $rows[1];
        $this->assertEquals('PB-01 Perencanaan', $item['nama']);
        $this->assertEquals('aktif', $item['value']);
        $this->assertEquals('option', $item['level_label']);
        $this->assertNull($rows[2]['value'], 'value null dipertahankan');

        // Provenance per row (kolom metadata §4.2)
        $this->assertEquals([
            'publisher' => 'Kemdikbud',
            'gurita' => 'https://gurita.kemdikbud.test',
            'dataset' => 'proses-bisnis',
            'version' => '2026.07',
            'imported_at' => '2026-07-14T10:00:00+07:00',
            'connection_id' => 7,
        ], $item['metadata']);
    }

    public function testToRowsContextAppOverridesTarget(): void
    {
        $rows = $this->adapter->toRows(self::payload(), ['app' => 'gov2pilot']);

        $this->assertEquals(['gov2pilot'], array_unique(array_column($rows, 'app')));
    }

    public function testToRowsDefaultsTypeAndInheritsPrivilege(): void
    {
        $payload = self::payload(['clusters' => [
            ['nama' => 'Polos', 'privilege' => 'webmaster',
                'items' => [['nama' => 'Tanpa Type', 'value' => '1']]],
        ]]);

        $rows = $this->adapter->toRows($payload);

        $this->assertEquals('text', $rows[1]['type'], 'type item default text');
        $this->assertEquals('webmaster', $rows[1]['privilege'], 'privilege item mewarisi cluster');
    }

    public function testToRowsCompatibleWithPinnedEnvelope(): void
    {
        // Rows import (dikurangi metadata, kolom DB-only) harus lolos validasi
        // resolver pinned — jalur import → pin (save-to-lower-tier) satu rantai
        $required = ['id', 'parent_id', 'app', 'nama', 'type', 'privilege',
            'status', 'value', 'level', 'level_label', 'keterangan'];

        foreach ($this->adapter->toRows(self::payload()) as $row) {
            $this->assertEquals([], array_diff($required, array_keys($row)));
        }
    }
}
