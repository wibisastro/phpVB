<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\gov2option;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the gov2option XML fallback (tier statis)
 *
 * Tests the pure static helpers backing the apps/{app}/xml/options.xml
 * fallback used when no database is configured:
 * - flattenOptionsXml(): cluster/item → flat rows shaped like the options table
 * - matchRows(): WHERE-like filtering (and/or, loose string equality)
 */
class Gov2optionXmlTest extends TestCase
{
    private function sampleXml(): \SimpleXMLElement
    {
        return new \SimpleXMLElement(<<<XML
            <options>
              <cluster name="Tahun dan Bulan" type="option" privilege="guest">
                <item name="Tahun" type="text" value="2026"/>
                <item name="Bulan" type="text" value="07" status="off"/>
              </cluster>
              <cluster name="MVC">
                <item name="dashboard" type="checkbox" value="1" privilege="webmaster"/>
              </cluster>
            </options>
            XML);
    }

    public function testFlattenProducesClusterAndItemRows(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        $this->assertCount(5, $rows);

        $cluster = $rows[0];
        $this->assertEquals(1, $cluster['id']);
        $this->assertEquals(0, $cluster['parent_id']);
        $this->assertEquals('home', $cluster['app']);
        $this->assertEquals('Tahun dan Bulan', $cluster['nama']);
        $this->assertEquals(1, $cluster['level']);
        $this->assertEquals('cluster', $cluster['level_label']);
        $this->assertEquals('guest', $cluster['privilege']);
        $this->assertEquals('on', $cluster['status']);

        $item = $rows[1];
        $this->assertEquals(2, $item['id']);
        $this->assertEquals(1, $item['parent_id']);
        $this->assertEquals('Tahun', $item['nama']);
        $this->assertEquals('2026', $item['value']);
        $this->assertEquals(2, $item['level']);
        $this->assertEquals('option', $item['level_label']);
    }

    public function testItemInheritsClusterPrivilegeAndCanOverride(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        $this->assertEquals('guest', $rows[1]['privilege']);      // inherit dari cluster
        $this->assertEquals('webmaster', $rows[4]['privilege']);  // override di item
        $this->assertEquals('admin', $rows[3]['privilege']);      // default cluster tanpa attr
    }

    public function testIdsAreSequentialAcrossClusters(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        $this->assertEquals([1, 2, 3, 4, 5], array_column($rows, 'id'));
        $this->assertEquals(4, $rows[4]['parent_id']); // item MVC menunjuk cluster ke-2
    }

    public function testMatchRowsAndSemantics(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        $match = gov2option::matchRows($rows, ['app' => 'home', 'nama' => 'Tahun']);

        $this->assertCount(1, $match);
        $this->assertEquals('2026', $match[0]['value']);
    }

    public function testMatchRowsParentIdLookup(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        // Pola getActiveYear(): cari anak dari cluster dengan value tertentu
        $parent = gov2option::matchRows($rows, ['nama' => 'MVC', 'level' => 1])[0];
        $children = gov2option::matchRows($rows, ['parent_id' => $parent['id'], 'value' => '1']);

        $this->assertCount(1, $children);
        $this->assertEquals('dashboard', $children[0]['nama']);
    }

    public function testMatchRowsOrSemantics(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        $match = gov2option::matchRows($rows, ['nama' => 'Tahun', 'value' => '1'], 'or');

        $this->assertCount(2, $match); // Tahun + dashboard(value=1)
    }

    public function testMatchRowsNoMatchReturnsEmpty(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        $this->assertEquals([], gov2option::matchRows($rows, ['nama' => 'TidakAda']));
    }

    public function testMatchRowsLevelOneFilterForGetAll(): void
    {
        $rows = gov2option::flattenOptionsXml($this->sampleXml(), 'home');

        // Pola getAll(): level 1 + status on
        $match = gov2option::matchRows($rows, ['app' => 'home', 'level' => 1, 'status' => 'on']);

        $this->assertEquals(['Tahun dan Bulan', 'MVC'], array_column($match, 'nama'));
    }
}
