<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Http\Router;
use Gov2lib\Contracts\RouteStatus;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gov2lib\Http\Router
 * 
 * Tests router functionality including:
 * - Adding and dispatching routes
 * - Extracting pageID, scriptID, and commandID from URIs
 * - Route not found and method not allowed handling
 * - Webroot stripping
 */
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router('/');
    }

    public function testAddRouteAndDispatch(): void
    {
        $this->router->addRoute('GET', '/api/users', 'UserController@index');

        $result = $this->router->dispatch('GET', '/api/users');

        $this->assertSame(RouteStatus::FOUND, $result->status);
        $this->assertTrue($result->isFound());
    }

    public function testDispatchReturnsNotFoundForUnknownUri(): void
    {
        $this->router->addRoute('GET', '/api/users', 'UserController@index');

        $result = $this->router->dispatch('GET', '/unknown/path');

        $this->assertSame(RouteStatus::NOT_FOUND, $result->status);
        $this->assertFalse($result->isFound());
    }

    public function testDispatchReturnsMethodNotAllowedForWrongMethod(): void
    {
        $this->router->addRoute('GET', '/api/users', 'UserController@index');

        $result = $this->router->dispatch('POST', '/api/users');

        $this->assertSame(RouteStatus::METHOD_NOT_ALLOWED, $result->status);
    }

    public function testResolvePageIdFromUri(): void
    {
        $pageId = $this->router->resolvePageId('/accounting/ap_vendor/list');

        $this->assertEquals('accounting', $pageId);
    }

    public function testResolvePageIdWithWebroot(): void
    {
        $router = new Router('/phpvb');
        $pageId = $router->resolvePageId('/phpvb/accounting/ap_vendor/list');

        $this->assertEquals('accounting', $pageId);
    }

    public function testGetPageIdAfterDispatch(): void
    {
        $this->router->addRoute('GET', '/{pageId}/{scriptId}/{cmd}', 'Handler');
        $this->router->dispatch('GET', '/users/profile/view');

        $pageId = $this->router->getPageId();

        $this->assertEquals('users', $pageId);
    }

    public function testGetScriptIdAfterDispatch(): void
    {
        $this->router->addRoute('GET', '/{pageId}/{scriptId}/{cmd}', 'Handler');
        $this->router->dispatch('GET', '/users/profile/view');

        $scriptId = $this->router->getScriptId();

        $this->assertEquals('profile', $scriptId);
    }

    public function testGetCommandIdAfterDispatch(): void
    {
        $this->router->addRoute('GET', '/{pageId}/{scriptId}/{cmd}', 'Handler');
        $this->router->dispatch('GET', '/users/profile/view');

        $commandId = $this->router->getCommandId();

        $this->assertEquals('view', $commandId);
    }

    public function testDispatchStructuredUri(): void
    {
        $this->router->addRoute('GET', '/accounting/ap_vendor/list', 'VendorController@list');

        $result = $this->router->dispatch('GET', '/accounting/ap_vendor/list');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testDispatchWithUriParameters(): void
    {
        $this->router->addRoute('GET', '/api/users/{id}', 'UserController@show');

        $result = $this->router->dispatch('GET', '/api/users/123');

        $this->assertSame(RouteStatus::FOUND, $result->status);
        $this->assertEquals(['id' => '123'], $result->vars);
    }

    public function testCaseInsensitiveHttpMethod(): void
    {
        $this->router->addRoute('post', '/api/users', 'UserController@store');

        $result = $this->router->dispatch('POST', '/api/users');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testMultipleRoutesWithDifferentMethods(): void
    {
        $this->router->addRoute('GET', '/api/users', 'UserController@index');
        $this->router->addRoute('POST', '/api/users', 'UserController@store');
        $this->router->addRoute('DELETE', '/api/users/{id}', 'UserController@destroy');

        $getResult = $this->router->dispatch('GET', '/api/users');
        $postResult = $this->router->dispatch('POST', '/api/users');
        $deleteResult = $this->router->dispatch('DELETE', '/api/users/42');

        $this->assertSame(RouteStatus::FOUND, $getResult->status);
        $this->assertSame(RouteStatus::FOUND, $postResult->status);
        $this->assertSame(RouteStatus::FOUND, $deleteResult->status);
    }

    public function testWebrootStripping(): void
    {
        $router = new Router('/phpvb');
        $router->addRoute('GET', '/users', 'UserController@index');

        $result = $router->dispatch('GET', '/phpvb/users');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testWebrootStrippingWithTrailingSlash(): void
    {
        $router = new Router('/phpvb/');
        $router->addRoute('GET', '/users', 'UserController@index');

        $result = $router->dispatch('GET', '/phpvb/users');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testDispatchReturnsHandler(): void
    {
        $this->router->addRoute('GET', '/api/users', 'UserController@index');

        $result = $this->router->dispatch('GET', '/api/users');

        $this->assertEquals('UserController@index', $result->handler);
    }

    public function testDispatchWithQueryString(): void
    {
        $this->router->addRoute('GET', '/api/users', 'UserController@index');

        $result = $this->router->dispatch('GET', '/api/users?page=1&sort=name');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testResolvePageIdWithQueryString(): void
    {
        $pageId = $this->router->resolvePageId('/accounting/ap_vendor/list?action=view');

        $this->assertEquals('accounting', $pageId);
    }

    public function testEmptyPageIdReturnsEmptyString(): void
    {
        $pageId = $this->router->resolvePageId('/');

        $this->assertEquals('', $pageId);
    }

    public function testGetPageIdWithoutDispatchReturnsEmpty(): void
    {
        $pageId = $this->router->getPageId();

        $this->assertEquals('', $pageId);
    }

    public function testGetScriptIdWithoutDispatchReturnsEmpty(): void
    {
        $scriptId = $this->router->getScriptId();

        $this->assertEquals('', $scriptId);
    }

    public function testGetCommandIdWithoutDispatchReturnsEmpty(): void
    {
        $commandId = $this->router->getCommandId();

        $this->assertEquals('', $commandId);
    }

    public function testDispatchResultHasAllProperties(): void
    {
        $this->router->addRoute('GET', '/api/users/{id}', 'UserController@show');

        $result = $this->router->dispatch('GET', '/api/users/999');

        $this->assertNotNull($result->status);
        $this->assertIsString($result->handler);
        $this->assertIsArray($result->vars);
        $this->assertIsString($result->pageId);
        $this->assertIsString($result->scriptId);
        $this->assertIsString($result->commandId);
    }

    public function testAddRouteEmptyMethodThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method and URI cannot be empty');

        $this->router->addRoute('', '/api/users', 'Handler');
    }

    public function testAddRouteEmptyUriThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Method and URI cannot be empty');

        $this->router->addRoute('GET', '', 'Handler');
    }

    public function testPostMethod(): void
    {
        $this->router->addRoute('POST', '/api/users', 'UserController@store');

        $result = $this->router->dispatch('POST', '/api/users');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testPutMethod(): void
    {
        $this->router->addRoute('PUT', '/api/users/{id}', 'UserController@update');

        $result = $this->router->dispatch('PUT', '/api/users/1');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testDeleteMethod(): void
    {
        $this->router->addRoute('DELETE', '/api/users/{id}', 'UserController@destroy');

        $result = $this->router->dispatch('DELETE', '/api/users/1');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testPatchMethod(): void
    {
        $this->router->addRoute('PATCH', '/api/users/{id}', 'UserController@patch');

        $result = $this->router->dispatch('PATCH', '/api/users/1');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testMultipleUriSegments(): void
    {
        $this->router->addRoute('GET', '/api/v1/users/{id}/posts/{postId}', 'PostController@show');

        $result = $this->router->dispatch('GET', '/api/v1/users/123/posts/456');

        $this->assertSame(RouteStatus::FOUND, $result->status);
        $this->assertEquals(['id' => '123', 'postId' => '456'], $result->vars);
    }

    public function testDispatchTrimsUri(): void
    {
        $this->router->addRoute('GET', '/api/users', 'UserController@index');

        $result = $this->router->dispatch('GET', '///api/users//');

        $this->assertTrue($result->isFound());
    }

    public function testRouterWithoutWebroot(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/users', 'UserController@index');

        $result = $router->dispatch('GET', '/users');

        $this->assertSame(RouteStatus::FOUND, $result->status);
    }

    public function testResolvePageIdFromStructuredUri(): void
    {
        $pageId = $this->router->resolvePageId('/admin/dashboard/view');

        $this->assertEquals('admin', $pageId);
    }
}
