<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

/**
 * Unit tests for gov2option::val() — API cross-calling (#6134 slice B)
 *
 * Resolusi scope: $app eksplisit > $GLOBALS['pageID'] > fallback home.
 * Mengembalikan value item level 2 by nama; shadowing lokal-menang
 * (entry yang ada di scope lokal menang meski value null).
 *
 * Scaffolding fixture ada di PinnedFixtureTestCase.
 */
class Gov2optionValTest extends PinnedFixtureTestCase
{
    public function testValReadsCurrentMvcScope(): void
    {
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));

        $this->assertEquals('2031', $this->opt(self::APP_XML)->val('Tahun'));
    }

    public function testValReadsXmlTierWithoutPinned(): void
    {
        // val() jalan di seluruh rantai — tanpa pinned, factory XML menjawab
        $this->assertEquals('2020', $this->opt(self::APP_XML)->val('Tahun'));
    }

    public function testValExplicitAppCrossCalling(): void
    {
        $rows = $this->sampleRows(self::APP_DB);
        $rows[1]['value'] = '2040';
        $this->writePinned(self::APP_DB, $rows);

        // pageID = APP_XML; argumen kedua menunjuk MVC lain
        $this->assertEquals('2040', $this->opt(self::APP_XML)->val('Tahun', self::APP_DB));
    }

    public function testValFallsBackToHome(): void
    {
        $homeRows = $this->sampleRows('home');
        $homeRows[1]['nama'] = 'discovery_url';
        $homeRows[1]['value'] = 'https://gurita.gov3.id';
        $this->writePinned('home', $homeRows);

        // Entry tidak ada di scope current (APP_XML) → fallback otomatis home
        $this->assertEquals('https://gurita.gov3.id', $this->opt(self::APP_XML)->val('discovery_url'));
    }

    public function testValLocalShadowsHome(): void
    {
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML)); // Tahun=2031
        $homeRows = $this->sampleRows('home');
        $homeRows[1]['value'] = '1999';
        $this->writePinned('home', $homeRows);

        $this->assertEquals('2031', $this->opt(self::APP_XML)->val('Tahun'));
    }

    public function testValPresenceShadowsEvenWhenValueNull(): void
    {
        // Shadowing berbasis KEBERADAAN entry: value null di scope lokal
        // TIDAK membuat resolusi lanjut ke home
        $rows = $this->sampleRows(self::APP_XML);
        $rows[1]['value'] = null;
        $this->writePinned(self::APP_XML, $rows);
        $homeRows = $this->sampleRows('home');
        $homeRows[1]['value'] = '1999';
        $this->writePinned('home', $homeRows);

        $this->assertNull($this->opt(self::APP_XML)->val('Tahun'));
    }

    public function testValReturnsOnlyLevelTwoItems(): void
    {
        // Cluster (level 1) bukan entry — val() hanya menjawab item level 2
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));

        $this->assertNull($this->opt(self::APP_XML)->val('Tahun dan Bulan'));
    }

    public function testValMissEverywhereReturnsNull(): void
    {
        $this->writePinned(self::APP_XML, $this->sampleRows(self::APP_XML));

        $this->assertNull($this->opt(self::APP_XML)->val('TidakAda'));
    }

    public function testValHomeScopeDoesNotDoubleLookup(): void
    {
        // pageID = home: kandidat scope unik (home saja), tetap resolve normal
        $homeRows = $this->sampleRows('home');
        $this->writePinned('home', $homeRows);

        $this->assertEquals('2031', $this->opt('home')->val('Tahun'));
        $this->assertNull($this->opt('home')->val('TidakAda'));
    }
}
