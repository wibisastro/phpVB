<?php
/********************************************************************
*	Date		: Mon, 9 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*********************************************************************/
//$doc->body('webroot',$config->webroot);
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

try {
    if ($httpMethod=='GET') {
        $doc->body('_GET',$vars);
    } else {
        $doc->body('_POST',$_POST);
    }

    $doc->body('STAGE',STAGE);
    $doc->body('pageID',$pageID);
    $doc->component($pageID);
    $doc->body('SSONODE',$config->platform->ssonode);
    // Token CSRF sesi (#6134 keputusan 9) — dipasang cubeHead sebagai default
    // header axios supaya semua POST mutation membawanya tanpa per-komponen
    $doc->body('csrfToken', Gov2lib\csrf::token());

    if (file_exists($self->templateDir."/body.html")) {
        $doc->baseBody="@$pageID/body.html";
    } else {
        $doc->baseBody="cubeBody.html";
    }

    if ($doc->error) {

        // 401 Unauthorized — belum login
        if ($doc->error['NotLogin']) {
            http_response_code(401);

        // 403 Forbidden — akses ditolak
        } else if ($doc->error['Forbidden'] || $doc->error['Unauthorized']) {
            http_response_code(403);
            $doc->baseBody="error403.html";

        // 404 Not Found — app/route/method tidak ditemukan
        } else if (
                    $doc->error['RouterConfigFileNotExist'] ||
                    $doc->error['ControllerClassNotExist'] ||
                    $doc->error['RouteNotFound'] ||
                    $doc->error['MethodNotExist'] ||
                    $doc->error['FunctionNotExist']
                    ) {
            http_response_code(404);
            $doc->baseBody="error404.html";

        // 409 Conflict — data duplikat
        } else if ($doc->error['AlreadyTagged']) {
            http_response_code(409);

        // 422 Unprocessable Entity — validasi / data error
        } else if (
                    $doc->error['ErrToken'] ||
                    $doc->error['ErrKeyRequest'] ||
                    $doc->error['ErrKeyResponse'] ||
                    $doc->error['IlegalAudience'] ||
                    $doc->error['InvalidDomain'] ||
                    $doc->error['UnlistedDomain']
                    ) {
            http_response_code(422);
            $doc->baseBody="error422.html";

        // 500 Internal Server Error — config / database / server
        } else if (
                    $doc->error['UnConfiguredDomain'] ||
                    $doc->error['InvalidConfigFile'] ||
                    $doc->error['ConfigFileNotExist'] ||
                    $doc->error['DatabaseError'] ||
                    $doc->error['DatabaseConnection'] ||
                    $doc->error['CannotConnectDSN'] ||
                    $doc->error['DBLinkError'] ||
                    $doc->error['DBQueryError'] ||
                    $doc->error['NoDSNConfigFile'] ||
                    $doc->error['InvalidDSNConfigFile'] ||
                    $doc->error['DSNEntryNotFound'] ||
                    $doc->error['DSNShareFileNotExist'] ||
                    $doc->error['InvalidDSNShareFile'] ||
                    $doc->error['TableConfigFileNotExist'] ||
                    $doc->error['InvalidTableConfigFile'] ||
                    $doc->error['TableShareFileNotExist'] ||
                    $doc->error['InvalidTableShareFile'] ||
                    $doc->error['InvalidRouterConfigFile'] ||
                    $doc->error['SuperUserShareFileNotExist'] ||
                    $doc->error['InvalidSuperuserShareFile']
                    ) {
            http_response_code(500);
            $doc->baseBody="error500.html";

        // 503 Service Unavailable — maintenance / waktu akses
        } else if ($doc->error['Maintenance'] || $doc->error['WrongTime'] || $doc->error['Closed']) {
            http_response_code(503);
            $doc->baseBody="error503.html";

        // Fallback
        } else {
            $doc->baseBody="errorBody.html";
        }

    }

    // Tema overlay (rencana/multi-template-bootstrap.md): core/template/{tema} ditaruh
    // DI DEPAN cube — tema cukup override file yang perlu (nama file cube* = nama
    // kontrak), sisanya fallback cube → general. v5.1.10 (Gov2lib\Tema): tema dipilih
    // PER APP lewat apps/{app}/xml/tema.xml <tema>nama</tema>; <template> config
    // portal = bawaan app yang tidak menetapkan sendiri; tanpa keduanya / nama tak
    // valid / folder tak ada = cube.
    $dirApp = (isset($self->templateDir) && is_string($self->templateDir) && $self->templateDir !== '')
        ? dirname($self->templateDir) : null;
    $pilihanTema = Gov2lib\Tema::pilih(__DIR__.'/../template', (string)($config->template ?? ''), $dirApp);
    $templates = $pilihanTema['templates'];
    $doc->body('tema', $pilihanTema['tema']);

    $vueData['isNavToggle'] = false;

    $loader = new FilesystemLoader($templates);

    // Tema sebagai namespace Twig (bootstrap:rencana/pabrik-tema.md, 13 Okt 2026):
    // setiap folder core/template/{nama} juga bisa dirujuk "@{nama}/file.html" dari
    // app mana pun — body.html app: {% extends "@landing-page/layout.html" %} —
    // tanpa mengubah overlay aktif <template>. Resolusi TANPA namespace tetap
    // [tema, cube, general] seperti sebelumnya (halaman yang ada byte-identik).
    foreach (glob(__DIR__.'/../template/*', GLOB_ONLYDIR) as $dirTema) {
        $loader->addPath($dirTema, basename($dirTema));
    }

    $twig = new Environment($loader,
        array(
            'auto_reload'=> true,
            'debug' => true,
            // 'strict_variables'=>true
        )
    );

    // Defensive: skip kalau templateDir tidak ada — Twig FilesystemLoader::addPath
    // throws kalau dir missing, dan exception itu di-catch di bawah → set $doc->error
    // → dispatcher kasih 401 (responseAuth). Ini terjadi misal di handler
    // Gov2lib\vue saat app yang request tidak punya direktori vue/ override
    // (mis. /ingest/vue/X.vue padahal apps/ingest/vue tidak exist).
    if (is_string($self->templateDir) && $self->templateDir !== '' && is_dir($self->templateDir)) {
        $loader->addPath((string) $self->templateDir, $pageID);
    }
    /**No required for twig version 2.16 or later */
    // $escaper = new Twig_Extension_Escaper('html');
    // $twig->addExtension($escaper);
    $twig->addExtension(new \Twig\Extension\DebugExtension());
    $twig->addExtension(new \Gov2lib\MarkdownExtension());

} catch (Exception $e) {
    $doc->exceptionHandler($e->getMessage());
}