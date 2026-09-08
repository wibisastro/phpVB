<?php

namespace Gov2lib;

/**
 * Temporary database connection instance.
 * Provides an easy DB access which works with the existing system.
 *
 * @since 2023
 * @version 2.0 - PHP 8.4 refactor
 */
class DBConnector
{
    /** @var array<string, mixed>|string */
    private array|string $dsn;

    public \MeekroDB $db;

    /**
     * Nama DB pengganti <db> entri DSN ('' = pakai <db> dari XML).
     * Dipakai Gov2lib\context untuk entri reader lintas-portal (gov3_reader)
     * yang sengaja tanpa <db> tetap: satu entri untuk 633 DB portal.
     */
    private string $dbOverride = '';

    /**
     * @param string $dsn        Nama entri <dsn> di dsnSource.{STAGE}.xml
     * @param string $dbOverride Nama DB pengganti; hanya dipakai bila cocok
     *                           ^[a-z0-9_]+$ (kl_kemenkeu, gov3_central), selain
     *                           itu diabaikan. Perilaku default tidak berubah.
     */
    public function __construct(string $dsn = 'master', string $dbOverride = '')
    {
        dsnSource::requireMeekroDB();

        $this->dsn = trim($dsn);
        $this->dbOverride = preg_match('/^[a-z0-9_]+$/', $dbOverride) ? $dbOverride : '';
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
                throw new \Exception("NoDSNConfigFile: apps/{$pageID}/xml/dsnSource." . STAGE . ".xml tidak ditemukan");
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
                    throw new \Exception("DSNShareFileNotExist: file DSN shared dari '{$list->share}' tidak ditemukan");
                }

                $sharedList = simplexml_load_file($sharedFile);

                if (!is_object($sharedList)) {
                    throw new \Exception("InvalidDSNShareFile: file DSN shared dari '{$list->share}' tidak valid");
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
                        'db' => $this->dbOverride !== '' ? $this->dbOverride : trim((string) $dsn->db),
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
