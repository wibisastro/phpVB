<?php

namespace Gov2lib;

/**
 * Temporary database connection instance.
 * Provides an easy DB access which works with the existing system.
 *
 * @since 2023
 * @version 2.0 - PHP 8.3 refactor
 */
class DBConnector
{
    /** @var array<string, mixed>|string */
    private array|string $dsn;

    public \MeekroDB $db;

    public function __construct(string $dsn = 'master')
    {
        $this->dsn = trim($dsn);
        $this->initializeDsn();
        $this->initializeDb();
    }

    /**
     * Parse DSN configuration from XML.
     */
    private function initializeDsn(): void
    {
        global $pageID, $doc;

        $dsnsPath = __DIR__ . "/../../apps/{$pageID}/xml/dsnSource." . STAGE . '.xml';

        try {
            if (!file_exists($dsnsPath)) {
                throw new \Exception("NoDSNConfigFile:{$dsnsPath}");
            }

            $list = simplexml_load_file($dsnsPath);

            if (!is_object($list)) {
                $errors = '';
                libxml_use_internal_errors(true);
                foreach (libxml_get_errors() as $error) {
                    $errors .= $error->message;
                }
                libxml_clear_errors();
                throw new \Exception("InvalidDSNConfigFile:{$errors}");
            }

            // Handle shared DSN
            if (!empty($list->share)) {
                $sharedFile = __DIR__ . "/../../apps/{$list->share}/xml/dsnSource." . STAGE . '.xml';

                if (!file_exists($sharedFile)) {
                    throw new \Exception("DSNShareFileNotExist:{$sharedFile}");
                }

                $sharedList = simplexml_load_file($sharedFile);

                if (!is_object($sharedList)) {
                    throw new \Exception("InvalidDSNShareFile:{$sharedFile}");
                }

                $list = $sharedList;
            }

            $dsnName = is_string($this->dsn) ? $this->dsn : '';

            foreach ($list->dsn as $dsn) {
                if (trim($dsnName) === trim((string) $dsn->name)) {
                    $this->dsn = [
                        'name' => trim((string) $dsn->name),
                        'user' => trim((string) $dsn->user),
                        'pass' => trim((string) $dsn->pass),
                        'host' => trim((string) $dsn->host),
                        'db' => trim((string) $dsn->db),
                        'port' => !empty($dsn->port) ? (int) trim((string) $dsn->port) : 3306,
                        'connect_options' => [MYSQLI_CLIENT_COMPRESS => true],
                    ];
                    break;
                }
            }
        } catch (\Exception $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Initialize MeekroDB instance with parsed DSN.
     */
    private function initializeDb(): void
    {
        global $doc;

        if (!is_array($this->dsn)) {
            return;
        }

        try {
            $this->db = new \MeekroDB(
                $this->dsn['host'],
                $this->dsn['user'],
                $this->dsn['pass'],
                $this->dsn['db'],
                $this->dsn['port']
            );
            $this->db->connect_options = $this->dsn['connect_options'];
        } catch (\MeekroDBException $e) {
            $doc->exceptionHandler($e->getMessage());
        }
    }
}
