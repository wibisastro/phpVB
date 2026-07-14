<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

/**
 * Factory options global home (#6134 slice E) — apps/home/xml/options.xml
 * nyata (bukan fixture): cluster Gurita harus terbaca lewat fallback home
 * val() dari app mana pun (dogfooding: panel Gurita membaca konfigurasinya
 * dari fitur yang ia layani).
 */
class Gov2optionHomeFactoryTest extends PinnedFixtureTestCase
{
    public function testGuritaDiscoveryConfigReadableViaHomeFallback(): void
    {
        // Scope app fixture (statis, tanpa entry gurita_*) → val() fallback
        // otomatis ke home → factory XML home
        $opt = $this->opt(self::APP_XML);

        $this->assertEquals('https://gurita.gov3.id', $opt->val('gurita_discovery_url'));
        $this->assertEquals('Gurita', $opt->val('gurita_label'));
    }

    public function testGuritaClusterShapeInFactoryXml(): void
    {
        $rows = $this->opt('home')->getAll('home');
        $gurita = array_values(array_filter($rows, fn (array $r): bool => $r['nama'] === 'Gurita'));

        $this->assertCount(1, $gurita, 'factory home wajib membawa cluster Gurita');
        $this->assertEquals('option', $gurita[0]['type']);
        $this->assertEquals('webmaster', $gurita[0]['privilege']);
    }
}
