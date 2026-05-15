<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Waffle\Handler\ControllerResponseConverter;
use WaffleTests\AbstractTestCase as TestCase;

#[AllowMockObjectsWithoutExpectations]
final class ControllerResponseConverterTest extends TestCase
{
    /**
     * Builds a stubbed ResponseFactory whose createResponse() returns a mock response
     * with a writable body stream; returns [factory, response, body] for inspection.
     *
     * @return array{0: ResponseFactoryInterface, 1: ResponseInterface, 2: StreamInterface}
     */
    private function buildFactory(): array
    {
        $body = $this->createMock(StreamInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('withHeader')->willReturnSelf();
        $response->method('getBody')->willReturn($body);

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturn($response);

        return [$factory, $response, $body];
    }

    public function testPassesThroughResponseInterface(): void
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);
        // The factory must NOT be called when the controller already returned a Response.
        $factory->expects($this->never())->method('createResponse');

        $existing = $this->createStub(ResponseInterface::class);

        $result = new ControllerResponseConverter($factory)->convert($existing);

        static::assertSame($existing, $result);
    }

    public function testNullProducesEmpty204(): void
    {
        [$factory, $response] = $this->buildFactory();
        $factory->expects($this->once())->method('createResponse')->with(204)->willReturn($response);

        $result = new ControllerResponseConverter($factory)->convert(null);

        static::assertSame($response, $result);
    }

    public function testArrayProducesJsonResponse(): void
    {
        [$factory, $response, $body] = $this->buildFactory();
        $factory->expects($this->once())->method('createResponse')->with(200)->willReturn($response);
        $body->expects($this->once())->method('write')->with('{"hello":"world"}');

        $result = new ControllerResponseConverter($factory)->convert(['hello' => 'world']);

        static::assertSame($response, $result);
    }

    public function testJsonSerializableProducesJsonResponse(): void
    {
        $payload = new class implements \JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['ok' => true];
            }
        };

        [$factory, $response, $body] = $this->buildFactory();
        $factory->expects($this->once())->method('createResponse')->with(200)->willReturn($response);
        $body->expects($this->once())->method('write')->with('{"ok":true}');

        $result = new ControllerResponseConverter($factory)->convert($payload);

        static::assertSame($response, $result);
    }

    public function testStringProducesHtmlResponse(): void
    {
        [$factory, $response, $body] = $this->buildFactory();
        $factory->expects($this->once())->method('createResponse')->with(200)->willReturn($response);
        $body->expects($this->once())->method('write')->with('<h1>Hello</h1>');

        $result = new ControllerResponseConverter($factory)->convert('<h1>Hello</h1>');

        static::assertSame($response, $result);
    }

    public function testUnsupportedTypeThrows(): void
    {
        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->expects($this->never())->method('createResponse');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Controller Error: Returned "int"');

        new ControllerResponseConverter($factory)->convert(42);
    }
}
