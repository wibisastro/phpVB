<?php

namespace Gov2lib;

/**
 * Image file server — handler route default /{app}/img/{file}.
 *
 * Route-nya sudah lama terdaftar di core/config/route.xml tapi class ini belum
 * pernah ada (css/js/vue ada, img tidak) — ketahuan saat app SSO beo (#6161,
 * deploy #6275) menyajikan logonya via /beo/img/slogo.png. Pola identik css.php:
 * resolusi file lewat checkExist (scandir exact-match → aman path traversal),
 * isi file dialirkan Gov2lib\index::index() (readfile).
 */
class img extends checkExist
{
    /**
     * Initialize image handler
     */
    public function __construct()
    {
        global $vars;
        $_app = $this->checkAppDir($vars["app"]);
        $this->baseName = $_app;
        $this->controller = __DIR__ . "/index.php";

        if (!isset($vars["file"])) {
            $vars["file"] = "";
        }

        $this->templateDir = __DIR__ . "/../../apps/" . $_app . "/img";
        $_file = $this->checkAppFile($_app . "/img", $vars["file"]);
        $this->componentName = $_file ?? '';

        $types = array(
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'ico'  => 'image/x-icon',
        );
        $_ext = strtolower(pathinfo((string) $vars["file"], PATHINFO_EXTENSION));
        header('Content-Type: ' . ($types[$_ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=86400');
    }
}
