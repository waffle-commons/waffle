<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Abstract\AbstractResponse;
use Waffle\Core\Constant;
use Waffle\Core\View;
use Waffle\Exception\RenderingException;
use WaffleTests\Abstract\Helper\ConcreteTestResponse;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Controller\TempController;

#[CoversClass(AbstractResponse::class)]
final class AbstractResponseTest extends TestCase
{
    /**
     * Tests that the render method correctly calls the controller action.
     * @throws RenderingException
     */
    public function testRenderCallsControllerActionAndView(): void
    {
        // 1. Setup: Create a real test instance of our abstract class.
        $_ENV['APP_ENV'] = 'test';
        $_SERVER['REQUEST_URI'] = '/users';
        $requestHandler = $this->createRealRequest(
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
        $container = $requestHandler->container;
        $container?->set(TempController::class, TempController::class);

        // 2. Configure the object state using its public methods.
        $requestHandler->setCurrentRoute(route: [
            Constant::CLASSNAME => TempController::class,
            Constant::METHOD => 'list',
            Constant::ARGUMENTS => [],
            Constant::PATH => '/users',
            Constant::NAME => 'user_list',
        ]);

        // 3. Create the response object with our configured handler.
        $response = new ConcreteTestResponse(handler: $requestHandler);

        // 4. Action: Call the render method.
        $response->render();

        // 5. Assert: Check the result.
        static::assertInstanceOf(View::class, $response->getView());
        $expectedData = [
            'id' => 1,
            'name' => 'John Doe',
        ];
        static::assertSame($expectedData, $response->getView()?->data);
    }

    /**
     * Tests that rendering throws an exception for a type mismatch in URL parameters.
     */
    public function testRenderThrowsExceptionForMismatchedArgumentType(): void
    {
        static::expectException(RenderingException::class);
        static::expectExceptionCode(400);
        static::expectExceptionMessage('URL parameter "id" expects type int, got invalid value: "abc".');

        // Setup
        $_ENV['APP_ENV'] = 'test';
        $_SERVER['REQUEST_URI'] = '/users/abc';
        $requestHandler = $this->createRealRequest(
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
        $container = $requestHandler->container;
        $container?->set(TempController::class, TempController::class);
        $requestHandler->setCurrentRoute(route: [
            Constant::CLASSNAME => TempController::class,
            Constant::METHOD => 'show',
            Constant::ARGUMENTS => ['id' => 'int'],
            Constant::PATH => '/users/{id}',
            Constant::NAME => 'user_show',
        ]);

        $response = new ConcreteTestResponse(handler: $requestHandler);

        // Action
        $response->render();
    }

    /**
     * Tests that the response can be built from a CLI handler.
     */
    public function testBuildFromCliHandler(): void
    {
        // Setup
        $cliHandler = $this->createRealCli();

        // Action
        $response = new ConcreteTestResponse(handler: $cliHandler);

        // Assert
        static::assertTrue($response->isCli());
    }
}
