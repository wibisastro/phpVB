<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Config\DatabaseConfig;
use Gov2lib\Contracts\RouteResult;
use Gov2lib\Contracts\RouteStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gov2lib\Config classes
 * 
 * Tests configuration classes including:
 * - DatabaseConfig construction and defaults
 * - DatabaseConfig DSN generation for different drivers
 * - DatabaseConfig creation from arrays and environment variables
 * - RouteResult readonly properties
 * - RouteStatus enum values
 */
class ConfigTest extends TestCase
{
    public function testDatabaseConfigConstructorDefaults(): void
    {
        $config = new DatabaseConfig();

        $this->assertEquals('localhost', $config->host);
        $this->assertEquals('root', $config->user);
        $this->assertEquals('', $config->password);
        $this->assertEquals('', $config->database);
        $this->assertEquals(3306, $config->port);
        $this->assertEquals('utf8mb4', $config->charset);
        $this->assertEquals('mysql', $config->driver);
    }

    public function testDatabaseConfigConstructorWithParameters(): void
    {
        $config = new DatabaseConfig(
            host: 'db.example.com',
            user: 'admin',
            password: 'secret',
            database: 'myapp',
            port: 5432,
            charset: 'utf8',
            driver: 'pgsql'
        );

        $this->assertEquals('db.example.com', $config->host);
        $this->assertEquals('admin', $config->user);
        $this->assertEquals('secret', $config->password);
        $this->assertEquals('myapp', $config->database);
        $this->assertEquals(5432, $config->port);
        $this->assertEquals('utf8', $config->charset);
        $this->assertEquals('pgsql', $config->driver);
    }

    public function testDatabaseConfigFromArrayWithDefaults(): void
    {
        $config = DatabaseConfig::fromArray([
            'host' => 'localhost',
            'user' => 'testuser',
            'database' => 'testdb'
        ]);

        $this->assertEquals('localhost', $config->host);
        $this->assertEquals('testuser', $config->user);
        $this->assertEquals('testdb', $config->database);
        $this->assertEquals('', $config->password);
        $this->assertEquals(3306, $config->port);
        $this->assertEquals('utf8mb4', $config->charset);
        $this->assertEquals('mysql', $config->driver);
    }

    public function testDatabaseConfigFromArrayComplete(): void
    {
        $array = [
            'host' => 'pg.server.com',
            'user' => 'pguser',
            'password' => 'pgpass',
            'database' => 'pgdb',
            'port' => 5432,
            'charset' => 'UTF8',
            'driver' => 'pgsql'
        ];

        $config = DatabaseConfig::fromArray($array);

        $this->assertEquals($array['host'], $config->host);
        $this->assertEquals($array['user'], $config->user);
        $this->assertEquals($array['password'], $config->password);
        $this->assertEquals($array['database'], $config->database);
        $this->assertEquals($array['port'], $config->port);
        $this->assertEquals($array['charset'], $config->charset);
        $this->assertEquals($array['driver'], $config->driver);
    }

    public function testDatabaseConfigToDsnMysql(): void
    {
        $config = new DatabaseConfig(
            host: 'localhost',
            user: 'root',
            password: '',
            database: 'myapp',
            port: 3306,
            charset: 'utf8mb4',
            driver: 'mysql'
        );

        $dsn = $config->toDsn();

        $this->assertStringStartsWith('mysql:', $dsn);
        $this->assertStringContainsString('host=localhost', $dsn);
        $this->assertStringContainsString('port=3306', $dsn);
        $this->assertStringContainsString('dbname=myapp', $dsn);
        $this->assertStringContainsString('charset=utf8mb4', $dsn);
    }

    public function testDatabaseConfigToDsnPostgres(): void
    {
        $config = new DatabaseConfig(
            host: 'pg.example.com',
            database: 'postgres_db',
            port: 5432,
            driver: 'pgsql'
        );

        $dsn = $config->toDsn();

        $this->assertStringStartsWith('pgsql:', $dsn);
        $this->assertStringContainsString('host=pg.example.com', $dsn);
        $this->assertStringContainsString('port=5432', $dsn);
        $this->assertStringContainsString('dbname=postgres_db', $dsn);
    }

    public function testDatabaseConfigToDsnSqlite(): void
    {
        $config = new DatabaseConfig(
            database: '/path/to/database.db',
            driver: 'sqlite'
        );

        $dsn = $config->toDsn();

        $this->assertEquals('sqlite:/path/to/database.db', $dsn);
    }

    public function testDatabaseConfigToDsnSqlServer(): void
    {
        $config = new DatabaseConfig(
            host: 'sqlserver.example.com',
            database: 'mydb',
            port: 1433,
            driver: 'sqlsrv'
        );

        $dsn = $config->toDsn();

        $this->assertStringStartsWith('sqlsrv:', $dsn);
        $this->assertStringContainsString('Server=sqlserver.example.com,1433', $dsn);
        $this->assertStringContainsString('Database=mydb', $dsn);
    }

    public function testDatabaseConfigFromEnvWithEnvVariables(): void
    {
        $_ENV['DB_HOST'] = 'env.host.com';
        $_ENV['DB_USER'] = 'envuser';
        $_ENV['DB_PASSWORD'] = 'envpass';
        $_ENV['DB_NAME'] = 'envdb';
        $_ENV['DB_PORT'] = '5432';
        $_ENV['DB_CHARSET'] = 'UTF8';
        $_ENV['DB_DRIVER'] = 'pgsql';

        $config = DatabaseConfig::fromEnv();

        $this->assertEquals('env.host.com', $config->host);
        $this->assertEquals('envuser', $config->user);
        $this->assertEquals('envpass', $config->password);
        $this->assertEquals('envdb', $config->database);
        $this->assertEquals(5432, $config->port);
        $this->assertEquals('UTF8', $config->charset);
        $this->assertEquals('pgsql', $config->driver);

        unset($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME'], $_ENV['DB_PORT'], $_ENV['DB_CHARSET'], $_ENV['DB_DRIVER']);
    }

    public function testDatabaseConfigFromEnvWithDefaults(): void
    {
        $_ENV['DB_HOST'] = 'custom.host';
        $_ENV['DB_USER'] = 'custom.user';

        $config = DatabaseConfig::fromEnv();

        $this->assertEquals('custom.host', $config->host);
        $this->assertEquals('custom.user', $config->user);
        $this->assertEquals('', $config->password);
        $this->assertEquals('', $config->database);
        $this->assertEquals(3306, $config->port);
        $this->assertEquals('utf8mb4', $config->charset);
        $this->assertEquals('mysql', $config->driver);

        unset($_ENV['DB_HOST'], $_ENV['DB_USER']);
    }

    public function testDatabaseConfigToArray(): void
    {
        $config = new DatabaseConfig(
            host: 'myhost',
            user: 'myuser',
            password: 'mypass',
            database: 'mydb',
            port: 3306,
            charset: 'utf8mb4',
            driver: 'mysql'
        );

        $array = $config->toArray();

        $this->assertEquals('myhost', $array['host']);
        $this->assertEquals('myuser', $array['user']);
        $this->assertEquals('mypass', $array['password']);
        $this->assertEquals('mydb', $array['database']);
        $this->assertEquals(3306, $array['port']);
        $this->assertEquals('utf8mb4', $array['charset']);
        $this->assertEquals('mysql', $array['driver']);
    }

    public function testDatabaseConfigToString(): void
    {
        $config = new DatabaseConfig(
            host: 'example.com',
            user: 'testuser',
            database: 'testdb',
            driver: 'mysql'
        );

        $str = (string)$config;

        $this->assertStringContainsString('mysql://', $str);
        $this->assertStringContainsString('testuser', $str);
        $this->assertStringContainsString('example.com', $str);
        $this->assertStringContainsString('testdb', $str);
    }

    public function testRouteResultHasReadonlyProperties(): void
    {
        $result = new RouteResult(
            status: RouteStatus::FOUND,
            handler: 'UserController@show',
            controller: 'UserController',
            vars: ['id' => '123'],
            pageId: 'users',
            scriptId: 'user',
            commandId: 'view'
        );

        $this->assertEquals(RouteStatus::FOUND, $result->status);
        $this->assertEquals('UserController@show', $result->handler);
        $this->assertEquals('UserController', $result->controller);
        $this->assertEquals(['id' => '123'], $result->vars);
        $this->assertEquals('users', $result->pageId);
        $this->assertEquals('user', $result->scriptId);
        $this->assertEquals('view', $result->commandId);
    }

    public function testRouteResultIsFoundMethod(): void
    {
        $foundResult = new RouteResult(status: RouteStatus::FOUND);
        $notFoundResult = new RouteResult(status: RouteStatus::NOT_FOUND);

        $this->assertTrue($foundResult->isFound());
        $this->assertFalse($notFoundResult->isFound());
    }

    public function testRouteStatusEnumValues(): void
    {
        $this->assertEquals('found', RouteStatus::FOUND->value);
        $this->assertEquals('not_found', RouteStatus::NOT_FOUND->value);
        $this->assertEquals('method_not_allowed', RouteStatus::METHOD_NOT_ALLOWED->value);
    }

    public function testRouteStatusEnumComparison(): void
    {
        $status1 = RouteStatus::FOUND;
        $status2 = RouteStatus::FOUND;
        $status3 = RouteStatus::NOT_FOUND;

        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
    }

    public function testDatabaseConfigReadonlyProperties(): void
    {
        $config = new DatabaseConfig(
            host: 'host.com',
            user: 'user',
            password: 'pass',
            database: 'db'
        );

        $this->assertEquals('host.com', $config->host);
        $this->assertEquals('user', $config->user);
        $this->assertEquals('pass', $config->password);
        $this->assertEquals('db', $config->database);
    }

    public function testDatabaseConfigMysqlDsnWithCharset(): void
    {
        $config = new DatabaseConfig(
            driver: 'mysql',
            database: 'testdb',
            host: 'localhost',
            port: 3306,
            charset: 'utf8mb4'
        );

        $dsn = $config->toDsn();

        $this->assertStringContainsString('charset=utf8mb4', $dsn);
    }

    public function testDatabaseConfigMysqlDsnWithoutCharset(): void
    {
        $config = new DatabaseConfig(
            driver: 'mysql',
            database: 'testdb',
            host: 'localhost',
            port: 3306,
            charset: ''
        );

        $dsn = $config->toDsn();

        $this->assertStringNotContainsString('charset=', $dsn);
        $this->assertStringContainsString('dbname=testdb', $dsn);
    }
}
