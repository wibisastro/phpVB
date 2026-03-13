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

    // 1. localhost → local
    if ($serverName === 'localhost') {
        $detectedStage = 'local';
    }

    // 2. XML multi-domain: scan config.*.xml, cari domain yang cocok
    if (!$detectedStage) {
        $configFiles = glob(__DIR__."/config.*.xml");
        foreach ($configFiles AS $configPath) {
            // Extract stage name dari filename: config.{stage}.xml
            preg_match('/config\.(.+)\.xml$/', basename($configPath), $matches);
            $stage = $matches[1] ?? null;
            if (!$stage) continue;

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
        default:
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