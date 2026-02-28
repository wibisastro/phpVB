<?php

declare(strict_types=1);

namespace Gov2lib\Config;

use SimpleXMLElement;

/**
 * DatabaseConfig is a readonly value object that encapsulates database connection configuration.
 * 
 * It replaces XML DSN parsing and provides multiple construction methods for flexibility:
 * - fromXml(): Load from existing XML DSN files (backward compatibility)
 * - fromEnv(): Load from environment variables
 * - fromArray(): Load from configuration array
 * 
 * This class provides methods for generating PDO DSN strings and applying configuration
 * to legacy systems like MeekroDB.
 */
readonly class DatabaseConfig
{
    /**
     * Constructor initializes database connection parameters.
     * 
     * @param string $host Database hostname
     * @param string $user Database username
     * @param string $password Database password
     * @param string $database Database name
     * @param int $port Database port
     * @param string $charset Database charset/collation
     * @param string $driver Database driver (mysql, pgsql, sqlite, etc.)
     */
    public function __construct(
        public string $host = 'localhost',
        public string $user = 'root',
        public string $password = '',
        public string $database = '',
        public int $port = 3306,
        public string $charset = 'utf8mb4',
        public string $driver = 'mysql',
    ) {}

    /**
     * Create DatabaseConfig from XML DSN file (backward compatibility).
     * 
     * Reads from the legacy XML DSN file structure: apps/{pageID}/xml/dsnSource.{stage}.xml
     * 
     * @param string $xmlPath Path to the XML DSN file
     * @return self
     * 
     * @throws \RuntimeException If XML file cannot be loaded or is invalid
     */
    public static function fromXml(string $xmlPath): self
    {
        if (!file_exists($xmlPath)) {
            throw new \RuntimeException("XML DSN file not found: {$xmlPath}");
        }

        try {
            $xml = simplexml_load_file($xmlPath);
            if ($xml === false) {
                throw new \RuntimeException("Failed to parse XML file: {$xmlPath}");
            }

            return self::parseXmlElement($xml);
        } catch (\Exception $e) {
            throw new \RuntimeException("Error loading database config from XML: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Parse SimpleXMLElement and extract database configuration.
     * 
     * @param SimpleXMLElement $xml
     * @return self
     */
    private static function parseXmlElement(SimpleXMLElement $xml): self
    {
        $host = (string)($xml->host ?? 'localhost');
        $user = (string)($xml->user ?? 'root');
        $password = (string)($xml->password ?? '');
        $database = (string)($xml->database ?? '');
        $port = (int)($xml->port ?? 3306);
        $charset = (string)($xml->charset ?? 'utf8mb4');
        $driver = (string)($xml->driver ?? 'mysql');

        return new self(
            host: $host,
            user: $user,
            password: $password,
            database: $database,
            port: $port,
            charset: $charset,
            driver: $driver,
        );
    }

    /**
     * Create DatabaseConfig from environment variables.
     * 
     * Reads from:
     * - DB_HOST
     * - DB_USER
     * - DB_PASSWORD
     * - DB_NAME
     * - DB_PORT (default: 3306)
     * - DB_CHARSET (default: utf8mb4)
     * - DB_DRIVER (default: mysql)
     * 
     * @return self
     */
    public static function fromEnv(): self
    {
        $host = $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'localhost';
        $user = $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? $_SERVER['DB_PASSWORD'] ?? '';
        $database = $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? '';
        $port = (int)($_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? 3306);
        $charset = $_ENV['DB_CHARSET'] ?? $_SERVER['DB_CHARSET'] ?? 'utf8mb4';
        $driver = $_ENV['DB_DRIVER'] ?? $_SERVER['DB_DRIVER'] ?? 'mysql';

        return new self(
            host: $host,
            user: $user,
            password: $password,
            database: $database,
            port: $port,
            charset: $charset,
            driver: $driver,
        );
    }

    /**
     * Create DatabaseConfig from configuration array.
     * 
     * Expected array keys:
     * - host, user, password, database, port, charset, driver
     * 
     * @param array<string, mixed> $config Configuration array
     * @return self
     */
    public static function fromArray(array $config): self
    {
        return new self(
            host: (string)($config['host'] ?? 'localhost'),
            user: (string)($config['user'] ?? 'root'),
            password: (string)($config['password'] ?? ''),
            database: (string)($config['database'] ?? ''),
            port: (int)($config['port'] ?? 3306),
            charset: (string)($config['charset'] ?? 'utf8mb4'),
            driver: (string)($config['driver'] ?? 'mysql'),
        );
    }

    /**
     * Generate PDO DSN string.
     * 
     * Supports multiple database drivers:
     * - mysql: mysql:host=...;port=...;dbname=...;charset=...
     * - pgsql: pgsql:host=...;port=...;dbname=...
     * - sqlite: sqlite:/path/to/database.db
     * 
     * @return string PDO DSN string
     * 
     * @throws \RuntimeException If driver is not supported
     */
    public function toDsn(): string
    {
        return match ($this->driver) {
            'mysql' => $this->getMysqlDsn(),
            'pgsql' => $this->getPostgresDsn(),
            'sqlite' => $this->getSqliteDsn(),
            'sqlsrv' => $this->getSqlServerDsn(),
            default => throw new \RuntimeException("Unsupported database driver: {$this->driver}"),
        };
    }

    /**
     * Generate MySQL DSN string.
     * 
     * @return string
     */
    private function getMysqlDsn(): string
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database}";
        
        if (!empty($this->charset)) {
            $dsn .= ";charset={$this->charset}";
        }

        return $dsn;
    }

    /**
     * Generate PostgreSQL DSN string.
     * 
     * @return string
     */
    private function getPostgresDsn(): string
    {
        return "pgsql:host={$this->host};port={$this->port};dbname={$this->database}";
    }

    /**
     * Generate SQLite DSN string.
     * 
     * @return string
     */
    private function getSqliteDsn(): string
    {
        return "sqlite:{$this->database}";
    }

    /**
     * Generate SQL Server DSN string.
     * 
     * @return string
     */
    private function getSqlServerDsn(): string
    {
        return "sqlsrv:Server={$this->host},{$this->port};Database={$this->database}";
    }

    /**
     * Apply configuration to MeekroDB static configuration (backward compatibility).
     * 
     * Configures the legacy MeekroDB query builder if available.
     * 
     * @return void
     * 
     * @throws \RuntimeException If MeekroDB is not available
     */
    public function applyToMeekroDB(): void
    {
        if (!class_exists('DB')) {
            throw new \RuntimeException('MeekroDB (DB class) is not available');
        }

        // Configure MeekroDB via static properties
        \DB::$host = $this->host;
        \DB::$user = $this->user;
        \DB::$password = $this->password;
        \DB::$dbName = $this->database;
        \DB::$port = $this->port;
        \DB::$encoding = $this->charset;

        // Additional configuration for MySQL
        if ($this->driver === 'mysql') {
            \DB::$charset = $this->charset;
        }
    }

    /**
     * Convert to array representation.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'user' => $this->user,
            'password' => $this->password,
            'database' => $this->database,
            'port' => $this->port,
            'charset' => $this->charset,
            'driver' => $this->driver,
        ];
    }

    /**
     * Get a summary of the configuration (without sensitive data).
     * 
     * @return string
     */
    public function __toString(): string
    {
        return "{$this->driver}://{$this->user}@{$this->host}:{$this->port}/{$this->database}";
    }
}
