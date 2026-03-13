<?php
/********************************************************************
*	Date		: Thursday, 05 Okt, 2017
*	Author		: Wibisono Sastrodiwiryo
*	Email		: wibi@alumni.ui.ac.id
*	Copyleft	: eGov Lab UI
*	Modified	: 13 Mar 2026 — APP_STAGE env var + stage detection
*********************************************************************/
try {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
    $publickey="c65ca73ce4c38dcec21151aa64f1590c";

    // Stage detection priority:
    // 1. APP_STAGE env var (most secure)
    // 2. localhost domain
    // 3. dev. prefix convention
    // 4. XML config domain mapping (legacy)
    // 5. fallback to prod

    $detectedStage = null;
    $serverName = $_SERVER["SERVER_NAME"] ?? '';

    // 1. Check APP_STAGE env var
    if (!$detectedStage && getenv('APP_STAGE')) {
        $detectedStage = getenv('APP_STAGE');
    }

    // 2. Check localhost
    if (!$detectedStage && $serverName === 'localhost') {
        $detectedStage = 'local';
    }

    // 3. Check dev. prefix
    if (!$detectedStage && strpos($serverName, 'dev.') === 0) {
        $detectedStage = 'dev';
    }

    // 4. Check XML config (legacy)
    if (!$detectedStage) {
        $availableStages = array('local', 'dev', 'prod');
        foreach ($availableStages AS $stage) {
            if (file_exists(__DIR__."/config.".$stage.".xml")) {
                $testConfig = simplexml_load_file(__DIR__."/config.".$stage.".xml");
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
    }

    // 5. Fallback to prod
    if (!$detectedStage) {
        $detectedStage = 'prod';
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