<?php

namespace Tests\Unit\Lib;

use Gov2lib\Exceptions\AuthenticationException;
use Gov2lib\Exceptions\AuthorizationException;
use Gov2lib\Exceptions\DatabaseException;
use Gov2lib\Exceptions\HttpException;
use Gov2lib\Exceptions\NotFoundException;
use Gov2lib\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

class ExceptionsTest extends TestCase
{
    public function test_http_exception_has_status_code(): void
    {
        $exception = new HttpException(500, 'Server Error');

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('Server Error', $exception->getMessage());
    }

    public function test_authentication_exception_defaults(): void
    {
        $exception = new AuthenticationException();

        $this->assertEquals(401, $exception->getStatusCode());
        $this->assertStringContainsString('login', $exception->getMessage());
    }

    public function test_authorization_exception_defaults(): void
    {
        $exception = new AuthorizationException();

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertStringContainsString('wewenang', $exception->getMessage());
    }

    public function test_validation_exception_has_errors(): void
    {
        $errors = ['nama' => 'Nama wajib diisi', 'email' => 'Email tidak valid'];
        $exception = new ValidationException('Validasi gagal', $errors);

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals($errors, $exception->getErrors());
    }

    public function test_not_found_exception_defaults(): void
    {
        $exception = new NotFoundException();

        $this->assertEquals(404, $exception->getStatusCode());
    }

    public function test_database_exception_defaults(): void
    {
        $exception = new DatabaseException('Query gagal');

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertEquals('Query gagal', $exception->getMessage());
    }

    public function test_to_legacy_format(): void
    {
        $exception = new AuthenticationException('Harus login dulu');

        $this->assertEquals('Authentication:Harus login dulu', $exception->toLegacyFormat());
    }
}
