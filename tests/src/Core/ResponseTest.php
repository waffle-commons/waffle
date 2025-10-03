<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Core\Cli;
use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Core\Response;
use Waffle\Exception\RenderingException;
use WaffleTests\Core\Helper\DummyControllerWithService;
use WaffleTests\Core\Helper\DummyService;
use WaffleTests\Router\Dummy\DummyController;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    private ?string $originalAppEnv;
    private array $originalServer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Store original superglobal states to avoid test leakage
        $this->originalAppEnv = $_ENV[Constant::APP_ENV] ?? null;
        $this->originalServer = $_SERVER;
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Restore original superglobal states
        if ($this->originalAppEnv === null) {
            unset($_ENV[Constant::APP_ENV]);
        } else {
            $_ENV[Constant::APP_ENV] = $this->originalAppEnv;
        }
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    /**
     * This test verifies that the Response class correctly renders the output
     * from a controller when handling a web request.
     */
    public function testRenderFromRequest(): void
    {
        // 1. Setup
        $request = new Request();
        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ]);

        $_ENV[Constant::APP_ENV] = 'dev'; // Ensure output is echoed

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean();

        // 3. Assertions
        $this->assertJson((string)$output);
        $expectedJson = json_encode(['data' => ['id' => 1, 'name' => 'John Doe']]);
        $this->assertJsonStringEqualsJsonString((string)$expectedJson, (string)$output);
    }

    /**
     * This test ensures that when the Response is constructed with a CLI handler,
     * it correctly identifies the context and does not attempt to render output
     * in the same way as a web request.
     */
    public function testRenderFromCli(): void
    {
        // 1. Setup
        $cli = new Cli();

        // 2. Action
        ob_start();
        $response = new Response(handler: $cli);
        $response->render(); // Should do nothing in CLI context for now
        $output = ob_get_clean();

        // 3. Assertions
        $this->assertEmpty($output);
    }

    /**
     * This test verifies that the Response class correctly resolves URL parameters
     * and passes them as arguments to the controller action.
     */
    public function testRenderWithUrlParameters(): void
    {
        // 1. Setup
        // We manipulate the superglobal BEFORE instantiating the Request object.
        $_SERVER[Constant::REQUEST_URI] = '/users/123';
        $request = new Request();

        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'show', // Assuming 'show' is the correct method for a single item
            Constant::ARGUMENTS => ['id' => 'int'],
            Constant::PATH => '/users/{id}',
            Constant::NAME => 'user_show',
        ]);

        $_ENV[Constant::APP_ENV] = 'dev';

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean();

        // 3. Assertions
        $this->assertJson((string)$output);
        $expectedData = ['data' => ['id' => 123, 'name' => 'John Doe']];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedData), (string)$output);
    }

    /**
     * This test confirms that the framework throws a specific RenderingException
     * when a URL parameter's type does not match the type hint in the controller action.
     */
    public function testRenderThrowsExceptionForMismatchedArgumentType(): void
    {
        // 1. Setup
        $this->expectException(RenderingException::class);
        $this->expectExceptionMessage('URL parameter "id" expects type int, got invalid value: "abc".');

        // Manipulate the superglobal with the invalid URI.
        $_SERVER[Constant::REQUEST_URI] = '/users/abc';
        $request = new Request();

        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'show',
            Constant::ARGUMENTS => ['id' => 'int'],
            Constant::PATH => '/users/{id}',
            Constant::NAME => 'user_show',
        ]);

        $_ENV[Constant::APP_ENV] = 'dev';

        // 2. Action
        $response = new Response(handler: $request);
        $response->render(); // This call should throw the exception.
    }

    /**
     * This test validates the basic dependency injection capability of the Response class.
     */
    public function testRenderInjectsSimpleService(): void
    {
        // 1. Setup
        $request = new Request();
        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyControllerWithService::class,
            Constant::METHOD => 'index',
            Constant::ARGUMENTS => ['service' => DummyService::class],
            Constant::PATH => '/service-test',
            Constant::NAME => 'service_test',
        ]);
        $_ENV[Constant::APP_ENV] = 'dev';

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean();

        // 3. Assertions
        // Decode the JSON and assert on the structure and specific values, ignoring dynamic ones.
        $this->assertJson((string)$output);
        $data = json_decode((string)$output, true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('service', $data['data']);
        $this->assertSame('injected', $data['data']['service']);
        $this->assertArrayHasKey('timestamp', $data['data']);
    }

    /**
     * This test verifies that no output is produced when the application environment
     * is set to 'test'.
     */
    public function testRenderProducesNoOutputWhenAppEnvIsTest(): void
    {
        // 1. Setup
        $request = new Request();
        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ]);

        $_ENV[Constant::APP_ENV] = 'test'; // Explicitly set the environment to 'test'

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean();

        // 3. Assertions
        $this->assertEmpty($output);
    }
}

