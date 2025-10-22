<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Core\Constant;
use Waffle\Core\Response;
use Waffle\Exception\RenderingException;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Core\Helper\DummyControllerWithService;
use WaffleTests\Core\Helper\DummyService;
use WaffleTests\Router\Dummy\DummyController;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    private mixed $originalAppEnv;

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
        $_ENV[Constant::APP_ENV] = $this->originalAppEnv;
        // Restore original superglobal states
        if (null === $this->originalAppEnv) {
            unset($_ENV[Constant::APP_ENV]);
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
        $_ENV[Constant::APP_ENV] = 'dev'; // Explicitly set the environment to 'dev'
        $request = $this->createRealRequest(
            level: 2,
            globals: [
                'server' => $_SERVER ?? [],
                'get' => $_GET ?? [],
                'post' => $_POST ?? [],
                'files' => $_FILES ?? [],
                'cookie' => $_COOKIE ?? [],
                'session' => $_SESSION ?? [],
                'request' => $_GET ?? [],
                'env' => $_ENV ?? [],
            ],
        );
        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ]);

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean() ?? '';

        // 3. Assertions
        static::assertJson($output);
        $expectedJson = json_encode(['data' => ['id' => 1, 'name' => 'John Doe']]);
        static::assertJsonStringEqualsJsonString((string) $expectedJson, $output);
    }

    /**
     * This test ensures that when the Response is constructed with a CLI handler,
     * it correctly identifies the context and does not attempt to render output
     * in the same way as a web request.
     */
    public function testRenderFromCli(): void
    {
        // 1. Setup
        $cli = $this->createRealCli();

        // 2. Action
        ob_start();
        $response = new Response(handler: $cli);
        $response->render(); // Should do nothing in CLI context for now
        $output = ob_get_clean() ?? '';

        // 3. Assertions
        static::assertEmpty($output);
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
        $_ENV[Constant::APP_ENV] = 'dev';
        $request = $this->createRealRequest(
            level: 2,
            globals: [
                'server' => $_SERVER ?? [],
                'get' => $_GET ?? [],
                'post' => $_POST ?? [],
                'files' => $_FILES ?? [],
                'cookie' => $_COOKIE ?? [],
                'session' => $_SESSION ?? [],
                'request' => $_GET ?? [],
                'env' => $_ENV ?? [],
            ],
        );

        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'show', // Assuming 'show' is the correct method for a single item
            Constant::ARGUMENTS => ['id' => 'int'],
            Constant::PATH => '/users/{id}',
            Constant::NAME => 'user_show',
        ]);

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean() ?? '';

        // 3. Assertions
        static::assertJson($output);
        $expectedData = json_encode(['data' => ['id' => 123, 'name' => 'John Doe']]);
        if (!$expectedData) {
            $expectedData = '';
        }
        static::assertJsonStringEqualsJsonString($expectedData, $output);
    }

    /**
     * This test confirms that the framework throws a specific RenderingException
     * when a URL parameter's type does not match the type hint in the controller action.
     */
    public function testRenderThrowsExceptionForMismatchedArgumentType(): void
    {
        // 1. Setup
        static::expectException(RenderingException::class);
        static::expectExceptionMessage('URL parameter "id" expects type int, got invalid value: "abc".');

        // Manipulate the superglobal with the invalid URI.
        $_SERVER[Constant::REQUEST_URI] = '/users/abc';
        $_ENV[Constant::APP_ENV] = 'dev';
        $request = $this->createRealRequest(
            level: 2,
            globals: [
                'server' => $_SERVER ?? [],
                'get' => $_GET ?? [],
                'post' => $_POST ?? [],
                'files' => $_FILES ?? [],
                'cookie' => $_COOKIE ?? [],
                'session' => $_SESSION ?? [],
                'request' => $_GET ?? [],
                'env' => $_ENV ?? [],
            ],
        );

        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'show',
            Constant::ARGUMENTS => ['id' => 'int'],
            Constant::PATH => '/users/{id}',
            Constant::NAME => 'user_show',
        ]);

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
        $_ENV[Constant::APP_ENV] = 'dev'; // Explicitly set the environment to 'dev'
        $_SERVER['REQUEST_URI'] = '/service-test'; // Explicitly set the request uri
        $request = $this->createRealRequest(
            level: 8,
            globals: [
                'server' => $_SERVER ?? [],
                'get' => $_GET ?? [],
                'post' => $_POST ?? [],
                'files' => $_FILES ?? [],
                'cookie' => $_COOKIE ?? [],
                'session' => $_SESSION ?? [],
                'request' => $_GET ?? [],
                'env' => $_ENV ?? [],
            ],
        );
        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyControllerWithService::class,
            Constant::METHOD => 'index',
            Constant::ARGUMENTS => ['service' => DummyService::class],
            Constant::PATH => '/service-test',
            Constant::NAME => 'service_test',
        ]);

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean() ?? '';

        // 3. Assertions
        // Decode the JSON and assert on the structure and specific values, ignoring dynamic ones.
        static::assertJson($output);
        /**
         * @var array{
         *        data: array{
         *         service: non-empty-string,
         *         timestamp: int
         *        }
         *    } $data
         */
        $data = json_decode($output, true);
        static::assertArrayHasKey('data', $data);
        static::assertArrayHasKey('service', $data['data']);
        static::assertSame('injected', $data['data']['service']);
        static::assertArrayHasKey('timestamp', $data['data']);
    }

    /**
     * This test verifies that no output is produced when the application environment
     * is set to 'test'.
     */
    public function testRenderProducesNoOutputWhenAppEnvIsTest(): void
    {
        // 1. Setup
        $_ENV[Constant::APP_ENV] = 'test'; // Explicitly set the environment to 'test'
        $request = $this->createRealRequest(
            level: 2,
            globals: [
                'server' => $_SERVER ?? [],
                'get' => $_GET ?? [],
                'post' => $_POST ?? [],
                'files' => $_FILES ?? [],
                'cookie' => $_COOKIE ?? [],
                'session' => $_SESSION ?? [],
                'request' => $_GET ?? [],
                'env' => $_ENV ?? [],
            ],
        );
        $request->setCurrentRoute([
            Constant::CLASSNAME => DummyController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ]);

        // 2. Action
        ob_start();
        $response = new Response(handler: $request);
        $response->render();
        $output = ob_get_clean() ?? '';

        // 3. Assertions
        static::assertEmpty($output);
    }
}
