<?php

namespace Gov2lib;

/**
 * Tema tampilan per app (v5.1.10).
 *
 * Tema admin (overlay `cube*`, folder core/template/{tema}) dulu hanya bisa dipilih per portal lewat
 * `<template>` di config. Arahan Wibi 14 Okt 2026: app yang berbeda boleh memakai tema yang berbeda
 * walau dalam satu portal. Urutan prioritas:
 *   1. apps/{app}/xml/tema.xml  — `<tema>gelap</tema>` (per app; `cube` eksplisit = tetap cube walau portal
 *      memakai tema lain);
 *   2. `<template>` config portal (bawaan semua app yang tidak menetapkan tema sendiri);
 *   3. cube.
 * Nama yang tidak valid / foldernya tidak ada → jatuh ke tingkat berikutnya. Tema halaman publik
 * (landing-page dsb.) tidak lewat sini: app meng-extend `@{tema}/layout.html` (namespace Twig per folder).
 *
 * Rujukan: core/init/template.php, release_notes/v5.1.10.md, bootstrap skill phpvb-bootstrap.
 */
final class Tema
{
    public const BAWAAN = 'cube';
    public const BERKAS_APP = 'xml/tema.xml';
    private const RE_NAMA = '/^[a-z0-9_-]+$/';

    /** Nama tema yang ditetapkan app di xml/tema.xml (`<tema>nama</tema>`), null bila tidak ada / kosong / tidak valid. */
    public static function bacaApp(string $dirApp): ?string
    {
        $berkas = rtrim($dirApp, '/') . '/' . self::BERKAS_APP;
        if ($dirApp === '' || !is_file($berkas)) {
            return null;
        }
        $xml = @simplexml_load_file($berkas);
        if ($xml === false) {
            return null;
        }
        $nama = strtolower(trim((string) $xml));
        return preg_match(self::RE_NAMA, $nama) === 1 ? $nama : null;
    }

    /** Tema boleh menimpa cube: nama valid, bukan cube/general, foldernya ada di core/template. */
    public static function valid(string $dirTemplate, ?string $nama): bool
    {
        $nama = strtolower(trim((string) $nama));
        return $nama !== '' && $nama !== self::BAWAAN && $nama !== 'general'
            && preg_match(self::RE_NAMA, $nama) === 1
            && is_dir(rtrim($dirTemplate, '/') . '/' . $nama);
    }

    /**
     * Tema aktif + urutan folder pencarian Twig (tanpa namespace): [tema?, cube, general].
     *
     * @return array{tema: string, templates: list<string>, sumber: string}  sumber = app|portal|bawaan
     */
    public static function pilih(string $dirTemplate, ?string $temaPortal, ?string $dirApp): array
    {
        $dirTemplate = rtrim($dirTemplate, '/');
        $templates = [$dirTemplate . '/' . self::BAWAAN, $dirTemplate . '/general'];
        $tema = self::BAWAAN;
        $sumber = 'bawaan';
        $app = $dirApp !== null && $dirApp !== '' ? self::bacaApp($dirApp) : null;
        if ($app !== null && ($app === self::BAWAAN || self::valid($dirTemplate, $app))) {
            $tema = $app;
            $sumber = 'app';
        } elseif (self::valid($dirTemplate, $temaPortal)) {
            $tema = strtolower(trim((string) $temaPortal));
            $sumber = 'portal';
        }
        if ($tema !== self::BAWAAN) {
            array_unshift($templates, $dirTemplate . '/' . $tema);
        }
        return ['tema' => $tema, 'templates' => $templates, 'sumber' => $sumber];
    }
}
