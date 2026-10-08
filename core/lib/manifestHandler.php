<?php

namespace Gov2lib;

use Gov2lib\Manifest\Endpoint;

/**
 * Model indeks manifest app (`/{app}/manifest`, controller Gov2lib\manifest): memindai `apps/{app}/view/{mvc}/manifest.md`
 * — manifest itu sendiri disajikan per MVC oleh cmd bawaan `manifest` (Gov2lib\Manifest\Endpoint, `/{app}/{mvc}/manifest`).
 * Tidak menyentuh DB app; DSN domain dihubungkan seperti roleHandler agar authenticate('guest') bisa membaca member.
 *
 * @since 2026-10-08 (v5.1.5; v5.1.7 = indeks per MVC)
 */
class manifestHandler extends checkExist
{
    public function __construct()
    {
        global $vars, $config, $pageID;

        parent::__construct(trim((string) ($config->domain->attr['dsn'] ?? '')));
        $_app = $this->checkAppDir((string) ($vars['app'] ?? $pageID ?? ''));
        $this->templateDir = __DIR__ . '/../../apps/' . $_app . '/view';
        $this->controller = __DIR__ . '/manifest.php';
    }

    /** Akar app (dipisah agar uji bisa menunjuk folder sementara). */
    public function dirApp(): string
    {
        global $pageID;

        return __DIR__ . "/../../apps/{$pageID}";
    }

    /** @return array<string, string> mvc → path manifest.md (urut nama) */
    public function daftarManifest(): array
    {
        $out = [];
        foreach (glob($this->dirApp() . '/view/*/manifest.md') ?: [] as $p) {
            $mvc = basename(dirname($p));
            if (preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $mvc)) {
                $out[$mvc] = $p;
            }
        }
        ksort($out);

        return $out;
    }

    /** Controller app (berkas {mvc}.php di akar app, selain index) yang belum punya view/{mvc}/manifest.md. @return list<string> */
    public function controllerTanpaManifest(): array
    {
        $ada = $this->daftarManifest();
        $out = [];
        foreach (glob($this->dirApp() . '/*.php') ?: [] as $p) {
            $mvc = basename($p, '.php');
            if ($mvc !== 'index' && !isset($ada[$mvc]) && preg_match('/^[A-Za-z][A-Za-z0-9_]{0,63}$/', $mvc)) {
                $out[] = $mvc;
            }
        }
        sort($out);

        return $out;
    }

    public function versiMvc(string $mvc, array $urai): string
    {
        if ($urai['versi'] !== '') {
            return $urai['versi'];
        }
        $rel = Endpoint::path($this->dirApp(), $mvc, 'release');

        return $rel ? Manifest\Parser::versiRelease(Endpoint::baca($rel)) : '';
    }
}
