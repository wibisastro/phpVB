<?php

declare(strict_types=1);

namespace Gov2lib\Config;

use Gov2lib\Contracts\ConfigInterface;
use SimpleXMLElement;

/**
 * AppConfig implements modern PHP-based configuration management for the phpVB framework.
 * 
 * This class loads and merges configuration from multiple sources:
 * - .env files via vlucas/phpdotenv
 * - Existing XML config files for backward compatibility
 * - Environment variables
 * 
 * All configuration is stored internally using dot-notation (e.g., 'database.host').
 */
class AppConfig implements ConfigInterface
{
    /**
     * @var array<string, mixed> Configuration stored in dot-notation
     */
    private array $config = [];

    /**
     * @var SimpleXMLElement|null Cached XML config for backward compatibility
     */
    private ?SimpleXMLElement $xmlConfig = null;

    /**
     * @var string The current application stage
     */
    private string $stage = 'local';

    /**
     * @var string The server domain name
     */
    private string $domain = '';

    /**
     * Constructor initializes configuration from multiple sources.
     * 
     * @param string $configDir Path to config directory (typically core/config)
     * @param string $serverName Server name for stage detection (typically from $_SERVER['SERVER_NAME'])
     * 
     * @throws \RuntimeException If configuration cannot be loaded
     */
    public function __construct(string $configDir = '', string $serverName = '')
    {
        // Determine config directory if not provided
        if (empty($configDir)) {
            $configDir = dirname(__DIR__, 3) . '/config';
        }

        // Load .env file if it exists
        $this->loadEnvFile();

        // Set domain
        $this->domain = $serverName ?: ($_SERVER['SERVER_NAME'] ?? 'localhost');

        // Detect stage from config XML and domain mapping
        $this->stage = $this->detectStage($configDir);

        // Load XML config for backward compatibility
        $this->loadXmlConfig($configDir);

        // Merge all configuration sources
        $this->mergeConfiguration();
    }

    /**
     * Load .env file from project root using vlucas/phpdotenv.
     * 
     * @return void
     */
    private function loadEnvFile(): void
    {
        // Find project root
        $projectRoot = dirname(__DIR__, 4);
        $envPath = $projectRoot . '/.env';

        if (!file_exists($envPath)) {
            return;
        }

        // Attempt to load .env using phpdotenv if available
        if (class_exists('Dotenv\Dotenv')) {
            $dotenv = new \Dotenv\Dotenv($projectRoot);
            $dotenv->safeLoad();
        } else {
            // Fallback: parse .env manually
            $this->parseEnvFile($envPath);
        }
    }

    /**
     * Manually parse .env file and populate $_ENV.
     * 
     * @param string $filePath Path to .env file
     * @return void
     */
    private function parseEnvFile(string $filePath): void
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parse KEY=VALUE
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^(["\'])(.*)\\1$/m', $value, $matches)) {
                    $value = $matches[2];
                }

                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
            }
        }
    }

    /**
     * Detect application stage from XML config domain mappings.
     * 
     * @param string $configDir Path to config directory
     * @return string Stage identifier (local/dev/prod)
     */
    private function detectStage(string $configDir): string
    {
        $stages = ['local', 'dev', 'prod'];

        foreach ($stages as $stage) {
            $configFile = $configDir . "/config.{$stage}.xml";
            if (!file_exists($configFile)) {
                continue;
            }

            try {
                $xml = simplexml_load_file($configFile);
                if ($xml === false) {
                    continue;
                }

                // Check if current domain matches any domain in config
                $domains = $xml->xpath('//config/domains/domain');
                foreach ($domains as $domainNode) {
                    $domainName = (string)$domainNode;
                    if ($domainName === $this->domain) {
                        return $stage;
                    }
                }
            } catch (\Exception $e) {
                // Continue to next stage
                continue;
            }
        }

        return 'local';
    }

    /**
     * Load and parse XML config file for the detected stage.
     * 
     * @param string $configDir Path to config directory
     * @return void
     */
    private function loadXmlConfig(string $configDir): void
    {
        $configFile = $configDir . "/config.{$this->stage}.xml";

        if (!file_exists($configFile)) {
            return;
        }

        try {
            $this->xmlConfig = simplexml_load_file($configFile);
            if ($this->xmlConfig !== false) {
                $this->fromXml($this->xmlConfig);
            }
        } catch (\Exception $e) {
            // Configuration file could not be loaded
        }
    }

    /**
     * Convert SimpleXMLElement to dot-notation array.
     * 
     * @param SimpleXMLElement $xml
     * @return void
     */
    private function fromXml(SimpleXMLElement $xml): void
    {
        // Map app/stage settings
        $stages = $xml->xpath('//config/stages/stage');
        foreach ($stages as $stageNode) {
            $stageName = (string)$stageNode->attributes()['id'] ?? '';
            if ($stageName === $this->stage) {
                $this->config['app.stage'] = $this->stage;
                $this->config['app.webroot'] = (string)($stageNode->webroot ?? '');
                $this->config['app.protocol'] = (string)($stageNode->protocol ?? 'http');
                $this->config['app.secure'] = $this->config['app.protocol'] === 'https';
            }
        }

        // Map database settings from DSN
        $dsnNodes = $xml->xpath('//config/database/dsn');
        if (!empty($dsnNodes)) {
            $dsn = $dsnNodes[0];
            $this->config['database.host'] = (string)($dsn->host ?? 'localhost');
            $this->config['database.user'] = (string)($dsn->user ?? 'root');
            $this->config['database.password'] = (string)($dsn->password ?? '');
            $this->config['database.name'] = (string)($dsn->database ?? '');
            $this->config['database.port'] = (int)($dsn->port ?? 3306);
            $this->config['database.charset'] = (string)($dsn->charset ?? 'utf8mb4');
            $this->config['database.driver'] = (string)($dsn->driver ?? 'mysql');
        }

        // Map JWT public key
        $publicKeyNodes = $xml->xpath('//config/security/publickey');
        if (!empty($publicKeyNodes)) {
            $this->config['app.publickey'] = (string)$publicKeyNodes[0];
        }

        // Map Keycloak settings
        $keycloakNodes = $xml->xpath('//config/keycloak/*');
        foreach ($keycloakNodes as $node) {
            $key = $node->getName();
            $this->config["keycloak.{$key}"] = (string)$node;
        }

        // Map platform SSO node
        $ssoNodes = $xml->xpath('//config/platform/ssonode');
        if (!empty($ssoNodes)) {
            $this->config['platform.ssonode'] = (string)$ssoNodes[0];
        }
    }

    /**
     * Merge configuration from .env and XML sources.
     * 
     * @return void
     */
    private function mergeConfiguration(): void
    {
        // Merge environment variables with higher priority
        if (isset($_ENV['DB_HOST'])) {
            $this->config['database.host'] = $_ENV['DB_HOST'];
        }
        if (isset($_ENV['DB_USER'])) {
            $this->config['database.user'] = $_ENV['DB_USER'];
        }
        if (isset($_ENV['DB_PASSWORD'])) {
            $this->config['database.password'] = $_ENV['DB_PASSWORD'];
        }
        if (isset($_ENV['DB_NAME'])) {
            $this->config['database.name'] = $_ENV['DB_NAME'];
        }
        if (isset($_ENV['DB_PORT'])) {
            $this->config['database.port'] = (int)$_ENV['DB_PORT'];
        }

        // Merge Supabase settings
        if (isset($_ENV['SUPABASE_URL'])) {
            $this->config['supabase.url'] = $_ENV['SUPABASE_URL'];
        }
        if (isset($_ENV['SUPABASE_ANON_KEY'])) {
            $this->config['supabase.anon_key'] = $_ENV['SUPABASE_ANON_KEY'];
        }
        if (isset($_ENV['SUPABASE_SERVICE_ROLE_KEY'])) {
            $this->config['supabase.service_role_key'] = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];
        }

        // Set defaults if not already set
        if (!isset($this->config['app.stage'])) {
            $this->config['app.stage'] = $this->stage;
        }
        if (!isset($this->config['app.domain'])) {
            $this->config['app.domain'] = $this->domain;
        }
    }

    /**
     * Get a configuration value by dot-notation key.
     * 
     * @param string $key Dot-notation key (e.g., 'database.host')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Check if a configuration key exists.
     * 
     * @param string $key Dot-notation key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }

    /**
     * Set a configuration value.
     * 
     * @param string $key Dot-notation key
     * @param mixed $value Value to set
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Get all configuration values.
     * 
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Get the current application stage.
     * 
     * @return string
     */
    public function getStage(): string
    {
        return $this->config['app.stage'] ?? $this->stage;
    }

    /**
     * Get the current domain.
     * 
     * @return string
     */
    public function getDomain(): string
    {
        return $this->config['app.domain'] ?? $this->domain;
    }

    /**
     * Get the application webroot path.
     * 
     * @return string
     */
    public function getWebroot(): string
    {
        return $this->config['app.webroot'] ?? '';
    }

    /**
     * Get the protocol (http or https).
     * 
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->config['app.protocol'] ?? 'http';
    }

    /**
     * Check if connection is secure (HTTPS).
     * 
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->config['app.secure'] ?? false;
    }

    /**
     * Get the original SimpleXMLElement for backward compatibility.
     * 
     * @return SimpleXMLElement|null
     */
    public function getXmlConfig(): ?SimpleXMLElement
    {
        return $this->xmlConfig;
    }
}
