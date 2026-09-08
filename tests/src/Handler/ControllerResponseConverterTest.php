<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Waffle\Handler\ControllerResponseConverter;
use Waffle\Handler\RawHtml;
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
        $response->method('withAddedHeader')->willReturnSelf();
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
        // A bare `string` return is escaped by default — this is the framework's
        // actual XSS defense for the text/html auto-conversion path, not the CSP
        // header alone (see testStringResponseAppliesCspDefenseInDepthHeaders()).
        [$factory, $response, $body] = $this->buildFactory();
        $factory->expects($this->once())->method('createResponse')->with(200)->willReturn($response);
        $body->expects($this->once())->method('write')->with('&lt;h1&gt;Hello&lt;/h1&gt;');

        $result = new ControllerResponseConverter($factory)->convert('<h1>Hello</h1>');

        static::assertSame($response, $result);
    }

    public function testStringResponseEscapesQuotesAndAmpersands(): void
    {
        [$factory, $response, $body] = $this->buildFactory();
        $body->expects($this->once())->method('write')->with('&quot;Tom &amp; Jerry&quot; &lt;b&gt;');

        new ControllerResponseConverter($factory)->convert('"Tom & Jerry" <b>');
    }

    public function testRawHtmlResponseIsNotEscaped(): void
    {
        // The explicit opt-out: a controller that vouches for its own markup
        // wraps it in RawHtml and gets it back byte-for-byte, unlike a bare string.
        [$factory, $response, $body] = $this->buildFactory();
        $factory->expects($this->once())->method('createResponse')->with(200)->willReturn($response);
        $body->expects($this->once())->method('write')->with('<h1>Hello</h1>');

        $result = new ControllerResponseConverter($factory)->convert(new RawHtml('<h1>Hello</h1>'));

        static::assertSame($response, $result);
    }

    public function testRawHtmlResponseStillReceivesCspAndNosniffHeaders(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();

        $addedHeaders = [];
        $response
            ->method('withAddedHeader')
            ->willReturnCallback(static function (string $name, mixed $value) use (
                $response,
                &$addedHeaders,
            ): ResponseInterface {
                $addedHeaders[$name] = $value;
                return $response;
            });

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturn($response);

        new ControllerResponseConverter($factory)->convert(new RawHtml('<h1>Hello</h1>'));

        static::assertSame(
            "default-src 'self'; form-action 'self'; base-uri 'self'",
            $addedHeaders['Content-Security-Policy'] ?? null,
        );
        static::assertSame('nosniff', $addedHeaders['X-Content-Type-Options'] ?? null);
    }

    public function testStringResponseAppliesCspDefenseInDepthHeaders(): void
    {
        // The CSP + nosniff floor is defense-in-depth, not the primary XSS
        // defense — that is the htmlspecialchars() escaping applied above. It
        // still narrows what an already-injected payload could do: no inline
        // script execution, no form-action/meta-refresh redirection, no
        // base-uri hijack.
        $body = $this->createMock(StreamInterface::class);
        $body->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();

        // Capture every withAddedHeader call so we can assert both headers landed.
        $addedHeaders = [];
        $response
            ->method('withAddedHeader')
            ->willReturnCallback(static function (string $name, mixed $value) use (
                $response,
                &$addedHeaders,
            ): ResponseInterface {
                $addedHeaders[$name] = $value;
                return $response;
            });

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturn($response);

        new ControllerResponseConverter($factory)->convert('<h1>Hello</h1>');

        static::assertSame(
            "default-src 'self'; form-action 'self'; base-uri 'self'",
            $addedHeaders['Content-Security-Policy'] ?? null,
        );
        static::assertSame('nosniff', $addedHeaders['X-Content-Type-Options'] ?? null);
    }

    public function testCustomCspOverridesDefault(): void
    {
        $body = $this->createMock(StreamInterface::class);
        $body->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();

        $addedHeaders = [];
        $response
            ->method('withAddedHeader')
            ->willReturnCallback(static function (string $name, mixed $value) use (
                $response,
                &$addedHeaders,
            ): ResponseInterface {
                $addedHeaders[$name] = $value;
                return $response;
            });

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturn($response);

        new ControllerResponseConverter(
            factory: $factory,
            stringResponseCsp: "default-src 'none'; img-src 'self'",
        )->convert('<img src="x">');

        static::assertSame("default-src 'none'; img-src 'self'", $addedHeaders['Content-Security-Policy'] ?? null);
        static::assertSame('nosniff', $addedHeaders['X-Content-Type-Options'] ?? null);
    }

    public function testNonStringResponsesDoNotReceiveCspHeaders(): void
    {
        // Array (→ JSON) path must NOT add CSP / nosniff — those belong to text/html.
        $body = $this->createMock(StreamInterface::class);
        $body->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();
        // Strict expectation: withAddedHeader must never be called for non-string paths.
        $response->expects($this->never())->method('withAddedHeader');

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $factory->method('createResponse')->willReturn($response);

        new ControllerResponseConverter($factory)->convert(['payload' => 'ok']);
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
