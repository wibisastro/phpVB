<?php

namespace Gov2lib;

/**
 * Database connection handler that loads DSN configurations from XML files.
 *
 * @author Wibisono Sastrodiwiryo <wibi@alumni.ui.ac.id>
 * @since 2017-09-29
 * @version 2.0 - PHP 8.4 refactor
 */
class dsnSource extends document
{
    protected ?\stdClass $tbl = null;
    protected string $dsnName = 'master';
    public int $scrollInterval = 1000;
    public ?array $api = null;

    public function __construct()
    {
        global $pageID;

        parent::__construct();

        require_once __DIR__ . '/../../vendor/sergeytsalkov/meekrodb/db.class.php';

        $this->loadTableConfig($pageID);
    }

    /**
     * Load table configuration from XML.
     */
    private function loadTableConfig(string $pageID): void
    {
        $tablesPath = __DIR__ . "/../../apps/{$pageID}/xml/dbTables.xml";

        try {
            if (!file_exists($tablesPath)) {
                throw new \Exception("TableConfigFileNotExist:{$tablesPath}");
            }

            $list = simplexml_load_file($tablesPath);

            if (!is_object($list)) {
                throw new \Exception("InvalidTableConfigFile:{$tablesPath}");
            }

            // Handle shared table config
            if (!empty($list->share)) {
                $sharedFile = __DIR__ . "/../../apps/{$list->share}/xml/dbTables.xml";

                if (!file_exists($sharedFile)) {
                    throw new \Exception("TableShareFileNotExist:{$sharedFile}");
                }

                $sharedList = simplexml_load_file($sharedFile);

                if (!is_object($sharedList)) {
                    throw new \Exception("InvalidTableShareFile:{$sharedFile}");
                }

                $list = $sharedList;
            }

            $this->tbl = new \stdClass();

            foreach ($list->table as $table) {
                $attribute = $table->attributes();
                $name = (string) ($attribute->name[0] ?? '');

                if ($name) {
                    $this->tbl->{$name} = $this->tbl->{$name} ?? $table;
                }
            }
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Establish a database connection using DSN configuration.
     *
     * @return array{0: \mysqli, 1: string, 2: mixed}|null
     */
    public function connectDB(string $dsnName = 'master'): ?array
    {
        global $pageID;

        if (!$dsnName) {
            $dsnName = 'master';
        }

        $dsnsPath = __DIR__ . "/../../apps/{$pageID}/xml/dsnSource." . STAGE . '.xml';

        try {
            if (!file_exists($dsnsPath)) {
                throw new \Exception("NoDSNConfigFile:{$dsnsPath}");
            }

            $list = simplexml_load_file($dsnsPath);

            if (!is_object($list)) {
                $errors = $this->getXmlErrors();
                throw new \Exception("InvalidDSNConfigFile:{$errors}");
            }

            $dsn = null;

            // Handle shared DSN config
            if (!empty($list->share)) {
                $sharedFile = __DIR__ . "/../../apps/{$list->share}/xml/dsnSource." . STAGE . '.xml';

                if (!file_exists($sharedFile)) {
                    throw new \Exception("DSNShareFileNotExist:{$sharedFile}");
                }

                $sharedList = simplexml_load_file($sharedFile);

                if (!is_object($sharedList)) {
                    throw new \Exception("InvalidDSNShareFile:{$sharedFile}");
                }

                $dsn = $this->credentialDB($sharedList, $dsnName);
            }

            if (!is_array($dsn)) {
                $dsn = $this->credentialDB($list, $dsnName);
            }

            $linkId = mysqli_connect(
                $dsn['host'],
                $dsn['user'],
                $dsn['pass'],
                $dsn['db'],
                (int) $dsn['port']
            );

            if ($linkId) {
                return [$linkId, $dsn['db'], null];
            }

            throw new \Exception('CannotConnectDSN:' . mysqli_connect_error() . " (dsnSource {$dsnName})");
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        }
    }

    /**
     * Extract database credentials from an XML DSN list.
     *
     * @return array{user: string, pass: string, host: string, port: string, db: string}|null
     */
    public function credentialDB(\SimpleXMLElement $list, string $dsnName): ?array
    {
        foreach ($list->dsn as $dsn) {
            if ($dsnName === trim((string) $dsn->name)) {
                $this->dsnName = $dsnName;

                $result = [
                    'user' => trim((string) $dsn->user),
                    'pass' => trim((string) $dsn->pass),
                    'host' => trim((string) $dsn->host),
                    'port' => !empty($dsn->port) ? trim((string) $dsn->port) : '3306',
                    'db' => trim((string) $dsn->db),
                ];

                // Configure MeekroDB static connection
                \DB::$user = $result['user'];
                \DB::$password = $result['pass'];
                \DB::$dbName = $result['db'];
                \DB::$host = $result['host'];
                \DB::$port = $result['port'];
                \DB::$connect_options = [MYSQLI_CLIENT_COMPRESS => true];

                return $result;
            }
        }

        return null;
    }

    /**
     * Execute a write query using raw mysqli connection.
     *
     * @deprecated Use MeekroDB \DB::insert() or \DB::update() instead.
     */
    public function writeDB(string $query, string $fname, string $table = ''): int|false
    {
        try {
            $connection = $this->connectDB($this->dsnName);

            if (!$connection) {
                throw new \Exception('DBLinkError:Cannot establish connection');
            }

            [$linkId, $dbName] = $connection;

            $this->queryDB($dbName, $query, $linkId);

            if (strlen($table) > 0) {
                $result = mysqli_fetch_object(
                    $this->queryDB($dbName, "SELECT LAST_INSERT_ID() AS id FROM {$table}", $linkId)
                );
                return (int) $result->id;
            }

            return false;
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
            return false;
        }
    }

    /**
     * Execute a raw database query.
     *
     * @deprecated Use MeekroDB \DB::query() instead.
     */
    public function queryDB(string $dbName, string $query, \mysqli $linkId): \mysqli_result|bool
    {
        try {
            $result = mysqli_query($linkId, $query);

            if ($result) {
                return $result;
            }

            throw new \Exception('DBQueryError:' . mysqli_error($linkId));
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
            return false;
        }
    }

    /**
     * Calculate scroll offset and limit for pagination.
     */
    public function scroll(int $scroll = 0): string
    {
        $scroll--;
        $interval = $this->scrollInterval ?: 1000;
        $offset = $scroll * $interval;

        return "{$offset},{$interval}";
    }

    /**
     * Load API connection credentials from DSN XML.
     */
    public function connectAPI(string $dsnName = 'master'): void
    {
        global $pageID;

        if (!$dsnName) {
            $dsnName = 'master';
        }

        $dsnsPath = __DIR__ . "/../../apps/{$pageID}/xml/dsnSource." . STAGE . '.xml';

        try {
            if (!file_exists($dsnsPath)) {
                throw new \Exception("NoDSNConfigFile:{$dsnsPath}");
            }

            $list = simplexml_load_file($dsnsPath);

            if (!is_object($list)) {
                throw new \Exception("InvalidDSNConfigFile:{$dsnsPath}");
            }

            $dsn = null;

            if (!empty($list->share)) {
                $sharedFile = __DIR__ . "/../../apps/{$list->share}/xml/dsnSource." . STAGE . '.xml';

                if (!file_exists($sharedFile)) {
                    throw new \Exception("DSNShareFileNotExist:{$sharedFile}");
                }

                $sharedList = simplexml_load_file($sharedFile);

                if (!is_object($sharedList)) {
                    throw new \Exception("InvalidDSNShareFile:{$sharedFile}");
                }

                $dsn = $this->credentialAPI($sharedList, $dsnName);
            }

            if (!is_array($dsn)) {
                $dsn = $this->credentialAPI($list, $dsnName);
            }

            $this->api = $dsn;
        } catch (\Exception $e) {
            $this->exceptionHandler($e->getMessage());
        }
    }

    /**
     * Extract API credentials from DSN XML.
     *
     * @return array{user: string, pass: string, host: string, port: string, db: string}|null
     */
    public function credentialAPI(\SimpleXMLElement $list, string $dsnName): ?array
    {
        foreach ($list->dsn as $dsn) {
            if ($dsnName === trim((string) $dsn->name)) {
                $this->dsnName = $dsnName;

                return [
                    'user' => trim((string) $dsn->user),
                    'pass' => trim((string) $dsn->pass),
                    'host' => trim((string) $dsn->host),
                    'port' => !empty($dsn->port) ? trim((string) $dsn->port) : '442',
                    'db' => trim((string) $dsn->db),
                ];
            }
        }

        return null;
    }

    /**
     * Get XML parsing errors as a string.
     */
    private function getXmlErrors(): string
    {
        libxml_use_internal_errors(true);
        $errors = '';

        foreach (libxml_get_errors() as $error) {
            $errors .= $error->message;
        }

        libxml_clear_errors();

        return $errors;
    }
}
