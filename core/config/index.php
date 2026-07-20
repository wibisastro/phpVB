<?php
/********************************************************************
*	Date		: Thursday, 05 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*	Modified	: 13 Mar 2026 — convention-based stage detection
*********************************************************************/
try {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    $publickey="c65ca73ce4c38dcec21151aa64f1590c";

    // Stage detection priority:
    // 1. localhost → local
    // 2. XML multi-domain → stage dari nama file config
    // 3. fallback → dev (domain belum terdaftar)
    //
    // Convention: domain dev masuk config.dev.xml, domain prod masuk config.prod.xml

    $detectedStage = null;
    $serverName = $_SERVER["SERVER_NAME"] ?? '';

    // R0 role-framework: STAGE 'local' mem-bypass SELURUH gate authenticate()
    // (gov2session.php: cabang STAGE != 'local'). SERVER_NAME mengikuti header
    // Host (Apache default UseCanonicalName Off), jadi tanpa proteksi ini siapa
    // pun bisa kirim "Host: localhost" ke server publik → STAGE local → semua
    // gate mati. Aturan: 'local' HANYA sah bila request dari mesin ini
    // (REMOTE_ADDR loopback). CLI/phpunit tak punya REMOTE_ADDR → tetap boleh.
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLoopback = ($remoteAddr === '' || $remoteAddr === '127.0.0.1' || $remoteAddr === '::1');

    // 1. localhost → local
    if ($serverName === 'localhost' && $isLoopback) {
        $detectedStage = 'local';
    }

    // 2. XML multi-domain: scan config.*.xml, cari domain yang cocok.
    //    Stage 'local' dari langkah ini WAJIB loopback juga (config.local.xml
    //    memuat <localhost> di blok domain — tanpa syarat ini, request remote
    //    ber-Host localhost akan terpilih 'local' lagi di sini, membatalkan
    //    proteksi langkah 1).
    if (!$detectedStage) {
        $configFiles = glob(__DIR__."/config.*.xml");
        foreach ($configFiles AS $configPath) {
            // Extract stage name dari filename: config.{stage}.xml
            preg_match('/config\.(.+)\.xml$/', basename($configPath), $matches);
            $stage = $matches[1] ?? null;
            if (!$stage) continue;
            if ($stage === 'local' && !$isLoopback) continue;

            $testConfig = simplexml_load_file($configPath);
            if (is_object($testConfig)) {
                if ($testConfig->domain->{$serverName}) {
                    $detectedStage = $stage;
                    break;
                }
            } else {
                throw new Exception('InvalidConfigFile:config.'.$stage.'.xml');
            }
        }
    }

    // 3. Fallback: domain belum terdaftar → dev
    if (!$detectedStage) {
        $detectedStage = 'dev';
    }

    define('STAGE', $detectedStage);

    // Load config file
    $configFile = __DIR__."/config.".STAGE.".xml";
    if (!file_exists($configFile)) {
        throw new Exception('ConfigFileNotExist:'.$configFile);
    }

    $config = simplexml_load_file($configFile);
    if (!is_object($config)) {
        throw new Exception('InvalidConfigFile:'.STAGE);
    }

    // Override kunci JWT Gov2Session per-server via <publickey> di config.{stage}.xml.
    // Default framework-wide dipakai 633 portal; server SSO beo WAJIB kunci sendiri
    // (K8 #6161) supaya kebocoran salinan beo tak bisa menempa cookie portal lain.
    if (!empty($config->publickey)) {
        $publickey = trim((string)$config->publickey);
    }

    // Set error reporting based on stage
    switch (STAGE) {
        case "local":
            ini_set("display_errors", 1);
            $_GET['error'] = isset($_GET['error']) ? $_GET['error'] : '';
            switch ($_GET['error']) {
                case "all":
                    error_reporting(E_ALL);
                    break;
                case "warning":
                    error_reporting(E_ALL & ~E_NOTICE);
                    break;
                default:
                    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
                    break;
            }
            break;
        case "dev":
        default:
            ini_set("display_errors", 1);
            $_GET['error'] = isset($_GET['error']) ? $_GET['error'] : '';
            switch ($_GET['error']) {
                case "all":
                    error_reporting(E_ALL);
                    break;
                case "warning":
                    error_reporting(E_ALL & ~E_NOTICE);
                    break;
                default:
                    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
                    break;
            }
            break;
        case "prod":
            error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
            ini_set("display_errors", 0);
            break;
    }

    if ($config->secure == true) {
        $config->protocol = "https";
    } else {
        $config->protocol = "http";
    }

    if (STAGE != 'local') {
        $domainNode = $config->domain->{$serverName};
        if ($domainNode) {
            foreach ($domainNode->attributes() as $k => $v) {
                $config->domain->attr[$k] = $v;
            }
        }
    }

    $config->domain->attr['dsn'] = $serverName;

} catch (Exception $e) {
    $config = new stdClass();
    $config->error = $e->getMessage();
}
?>