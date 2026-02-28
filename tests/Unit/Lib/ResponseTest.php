<?php

declare(strict_types=1);

namespace Tests\Unit\Lib;

use Gov2lib\Http\Response;
use Gov2lib\Http\ExceptionHandler;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gov2lib\Http\Response
 * 
 * Tests HTTP response building including:
 * - Success response array formatting
 * - Error response array formatting
 * - Legacy message parsing (Code:Message format)
 * - Response structure validation
 */
class ResponseTest extends TestCase
{
    public function testSuccessReturnsCorrectArrayFormat(): void
    {
        $response = Response::success('Operation successful');

        $this->assertIsArray($response);
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('success', $response['class']);
        $this->assertEquals('Operation successful', $response['message']);
        $this->assertEquals('Operation successful', $response['notification']);
        $this->assertEquals('', $response['callback']);
        $this->assertEquals(0, $response['id']);
        $this->assertTrue($response['success']);
    }

    public function testSuccessWithCallback(): void
    {
        $response = Response::success('Record created', 'refreshTable', 123);

        $this->assertEquals('Record created', $response['message']);
        $this->assertEquals('refreshTable', $response['callback']);
        $this->assertEquals(123, $response['id']);
        $this->assertTrue($response['success']);
    }

    public function testSuccessWithoutCallback(): void
    {
        $response = Response::success('Success');

        $this->assertEquals('', $response['callback']);
        $this->assertEquals(0, $response['id']);
        $this->assertTrue($response['success']);
    }

    public function testErrorReturnsCorrectArrayFormat(): void
    {
        $response = Response::error('error', 'Something went wrong');

        $this->assertIsArray($response);
        $this->assertEquals(422, $response['status']);
        $this->assertEquals('error', $response['class']);
        $this->assertEquals('Something went wrong', $response['message']);
        $this->assertEquals('Something went wrong', $response['notification']);
        $this->assertFalse($response['success']);
    }

    public function testErrorWithCustomStatus(): void
    {
        $response = Response::error('error', 'Not found', 404);

        $this->assertEquals(404, $response['status']);
        $this->assertEquals('error', $response['class']);
        $this->assertFalse($response['success']);
    }

    public function testErrorWithWarningClass(): void
    {
        $response = Response::error('warning', 'Warning message');

        $this->assertEquals('warning', $response['class']);
        $this->assertFalse($response['success']);
    }

    public function testErrorWithDangerClass(): void
    {
        $response = Response::error('danger', 'Danger message');

        $this->assertEquals('danger', $response['class']);
        $this->assertFalse($response['success']);
    }

    public function testParseLegacyMessageWithCodeAndMessage(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('404:Page not found');

        $this->assertIsArray($result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Page not found', $result['message']);
    }

    public function testParseLegacyMessageWithWhitespace(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('401 : Unauthorized');

        $this->assertEquals(401, $result['code']);
        $this->assertEquals('Unauthorized', $result['message']);
    }

    public function testParseLegacyMessageWithMultipleColons(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('500:Database error: connection timeout');

        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Database error: connection timeout', $result['message']);
    }

    public function testParseLegacyMessageWithSimpleMessage(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('This is a simple error message');

        $this->assertEmpty($result);
    }

    public function testParseLegacyMessageWithEmptyString(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('');

        $this->assertEmpty($result);
    }

    public function testParseLegacyMessageWithNonNumericCode(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('Error:Something went wrong');

        $this->assertEmpty($result);
    }

    public function testParseLegacyMessageWithFloatCode(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('404.5:Not quite found');

        $this->assertEmpty($result);
    }

    public function testParseLegacyMessagePreservesLeadingZeros(): void
    {
        $result = ExceptionHandler::parseLegacyMessage('007:Secret code');

        $this->assertEquals(7, $result['code']);
        $this->assertEquals('Secret code', $result['message']);
    }

    public function testErrorArrayStructure(): void
    {
        $response = Response::error('error', 'Test error', 500);

        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('class', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('notification', $response);
        $this->assertArrayHasKey('success', $response);
    }

    public function testSuccessArrayStructure(): void
    {
        $response = Response::success('Test success', 'callback', 42);

        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('class', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('notification', $response);
        $this->assertArrayHasKey('callback', $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('success', $response);
    }

    public function testSuccessStatusAlways200(): void
    {
        $response1 = Response::success('First');
        $response2 = Response::success('Second', 'callback');
        $response3 = Response::success('Third', '', 999);

        $this->assertEquals(200, $response1['status']);
        $this->assertEquals(200, $response2['status']);
        $this->assertEquals(200, $response3['status']);
    }

    public function testErrorStatusDefaults(): void
    {
        $response = Response::error('error', 'Message');

        $this->assertEquals(422, $response['status']);
    }

    public function testMessageAndNotificationAreSame(): void
    {
        $response = Response::success('Same message');

        $this->assertEquals($response['message'], $response['notification']);
    }

    public function testErrorMessageAndNotificationAreSame(): void
    {
        $response = Response::error('error', 'Error message');

        $this->assertEquals($response['message'], $response['notification']);
    }
}
