<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gov2lib\Http\Request
 * 
 * Tests HTTP request handling including:
 * - Constructor with all parameters
 * - Input retrieval from GET, POST, and merged sources
 * - Request method detection
 * - AJAX detection via Accept header
 * - Command extraction from POST or URI
 * - Server variable and header access
 */
class RequestTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $request = new Request(
            method: 'POST',
            uri: '/phpvb/test/script/action?foo=bar',
            pageId: 'test',
            scriptId: 'script',
            commandId: 'action',
            get: ['foo' => 'bar'],
            post: ['name' => 'John'],
            cookies: ['session' => 'abc123'],
            server: ['HTTP_HOST' => 'example.com'],
            files: []
        );

        $this->assertEquals('POST', $request->method);
        $this->assertEquals('/phpvb/test/script/action?foo=bar', $request->uri);
        $this->assertEquals('test', $request->pageId);
        $this->assertEquals('script', $request->scriptId);
        $this->assertEquals('action', $request->commandId);
    }

    public function testInputRetrievesFromPostFirst(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            get: ['key' => 'from_get'],
            post: ['key' => 'from_post']
        );

        $this->assertEquals('from_post', $request->input('key'));
    }

    public function testInputFallsBackToGet(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            get: ['key' => 'from_get'],
            post: []
        );

        $this->assertEquals('from_get', $request->input('key'));
    }

    public function testInputReturnsDefaultWhenNotFound(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            get: [],
            post: []
        );

        $this->assertEquals('default', $request->input('missing', 'default'));
        $this->assertNull($request->input('missing'));
    }

    public function testQueryRetrievesFromGetOnly(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            get: ['key' => 'from_get'],
            post: ['key' => 'from_post']
        );

        $this->assertEquals('from_get', $request->query('key'));
    }

    public function testPostRetrievesFromPostOnly(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            get: ['key' => 'from_get'],
            post: ['key' => 'from_post']
        );

        $this->assertEquals('from_post', $request->post('key'));
    }

    public function testCookieRetrieval(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            cookies: ['session_id' => 'xyz789']
        );

        $this->assertEquals('xyz789', $request->cookie('session_id'));
        $this->assertNull($request->cookie('nonexistent'));
    }

    public function testServerVariableRetrieval(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            server: ['HTTP_HOST' => 'example.com', 'SERVER_PORT' => '80']
        );

        $this->assertEquals('example.com', $request->server('HTTP_HOST'));
        $this->assertEquals('80', $request->server('SERVER_PORT'));
        $this->assertNull($request->server('NONEXISTENT'));
    }

    public function testHeaderRetrieval(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            server: [
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer token123'
            ]
        );

        $this->assertEquals('application/json', $request->header('Content-Type'));
        $this->assertEquals('Bearer token123', $request->header('Authorization'));
    }

    public function testHeaderNameNormalization(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            server: ['HTTP_X_CUSTOM_HEADER' => 'custom_value']
        );

        $this->assertEquals('custom_value', $request->header('X-Custom-Header'));
    }

    public function testIsAjaxDetectsJsonAcceptHeader(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            server: ['HTTP_ACCEPT' => 'application/json, text/plain']
        );

        $this->assertTrue($request->isAjax());
    }

    public function testIsAjaxReturnsFalseWithoutJsonAccept(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            server: ['HTTP_ACCEPT' => 'text/html, application/xhtml+xml']
        );

        $this->assertFalse($request->isAjax());
    }

    public function testIsAjaxReturnsFalseWhenHeaderMissing(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            server: []
        );

        $this->assertFalse($request->isAjax());
    }

    public function testIsMethodChecksHttpMethod(): void
    {
        $request = new Request(
            'POST',
            '/test',
            'test',
            'script',
            'action'
        );

        $this->assertTrue($request->isMethod('POST'));
        $this->assertTrue($request->isMethod('post'));
        $this->assertFalse($request->isMethod('GET'));
    }

    public function testIsMethodCaseInsensitive(): void
    {
        $request = new Request(
            'put',
            '/test',
            'test',
            'script',
            'action'
        );

        $this->assertTrue($request->isMethod('PUT'));
        $this->assertTrue($request->isMethod('put'));
    }

    public function testAllMergesGetAndPost(): void
    {
        $request = new Request(
            'POST',
            '/test',
            'test',
            'script',
            'action',
            get: ['foo' => 'bar', 'shared' => 'from_get'],
            post: ['baz' => 'qux', 'shared' => 'from_post']
        );

        $all = $request->all();

        $this->assertEquals('bar', $all['foo']);
        $this->assertEquals('qux', $all['baz']);
        $this->assertEquals('from_post', $all['shared']);
    }

    public function testGetCommandFromPost(): void
    {
        $request = new Request(
            'POST',
            '/test',
            'test',
            'script',
            'uri_action',
            post: ['cmd' => 'post_action']
        );

        $this->assertEquals('post_action', $request->getCommand());
    }

    public function testGetCommandFromUri(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'uri_action',
            post: []
        );

        $this->assertEquals('uri_action', $request->getCommand());
    }

    public function testGetCommandReturnsEmptyString(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            '',
            post: []
        );

        $this->assertEquals('', $request->getCommand());
    }

    public function testFilesRetrieval(): void
    {
        $files = [
            'avatar' => [
                'name' => 'profile.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/xyz',
                'error' => UPLOAD_ERR_OK,
                'size' => 5000
            ]
        ];

        $request = new Request(
            'POST',
            '/test',
            'test',
            'script',
            'action',
            files: $files
        );

        $this->assertEquals($files, $request->files());
        $this->assertEquals($files['avatar'], $request->files('avatar'));
    }

    public function testHasFileChecksUploadSuccess(): void
    {
        $files = [
            'avatar' => [
                'name' => 'profile.jpg',
                'error' => UPLOAD_ERR_OK,
            ],
            'failed' => [
                'name' => 'bad.jpg',
                'error' => UPLOAD_ERR_NO_FILE,
            ]
        ];

        $request = new Request(
            'POST',
            '/test',
            'test',
            'script',
            'action',
            files: $files
        );

        $this->assertTrue($request->hasFile('avatar'));
        $this->assertFalse($request->hasFile('failed'));
        $this->assertFalse($request->hasFile('nonexistent'));
    }

    public function testMethodNormalization(): void
    {
        $request = new Request(
            'get',
            '/test',
            'test',
            'script',
            'action'
        );

        $this->assertEquals('GET', $request->method);
        $this->assertTrue($request->isMethod('GET'));
    }

    public function testPayloadForPost(): void
    {
        $request = new Request(
            'POST',
            '/test',
            'test',
            'script',
            'action',
            get: ['foo' => 'bar'],
            post: ['name' => 'John', 'age' => '30']
        );

        $this->assertEquals(['name' => 'John', 'age' => '30'], $request->getPayload());
    }

    public function testPayloadForGet(): void
    {
        $request = new Request(
            'GET',
            '/test',
            'test',
            'script',
            'action',
            get: ['search' => 'test'],
            post: ['ignored' => 'value']
        );

        $this->assertEquals(['search' => 'test'], $request->getPayload());
    }

    public function testPayloadForDelete(): void
    {
        $request = new Request(
            'DELETE',
            '/test',
            'test',
            'script',
            'action',
            post: ['id' => '123']
        );

        $this->assertEquals(['id' => '123'], $request->getPayload());
    }
}
