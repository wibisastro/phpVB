<?php

namespace Gov2lib;

/**
 * Model endpoint agen sesi (`/{app}/agen`, controller Gov2lib\agen).
 *
 * Mewarisi agendbHandler hanya untuk `cariMember()` (DSN `master` app, koneksi mysqli sesi READ ONLY
 * + batas waktu) — tidak membuka DSN app dan tidak menjalankan kueri lain. Tidak ada akun DB baru.
 *
 * @since 2026-10-08 (v5.1.6)
 */
class agenHandler extends agendbHandler
{
    public function __construct()
    {
        parent::__construct();
        $this->controller = __DIR__ . '/agen.php';
    }
}
