<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Abstract\AbstractResponse;
use Waffle\Core\Constant;
use Waffle\Core\View;
use Waffle\Exception\RenderingException;
use WaffleTests\Abstract\Helper\ConcreteTestResponse;
use WaffleTests\Abstract\Helper\TestCli;
use WaffleTests\Abstract\Helper\TestRequest;
use WaffleTests\Router\Dummy\DummyController;

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
        $requestHandler = new TestRequest();

        // 2. Configure the object state using its public methods.
        $_ENV['APP_ENV'] = 'test';
        $_SERVER['REQUEST_URI'] = '/users';
        $requestHandler->setCurrentRoute(route: [
            Constant::CLASSNAME => DummyController::class,
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
        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('URL parameter "id" expects type int, got invalid value: "abc".');

        // Setup
        $requestHandler = new TestRequest();
        $_ENV['APP_ENV'] = 'test';
        $_SERVER['REQUEST_URI'] = '/users/abc';
        $requestHandler->setCurrentRoute(route: [
            Constant::CLASSNAME => DummyController::class,
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
        $cliHandler = new TestCli();

        // Action
        $response = new ConcreteTestResponse(handler: $cliHandler);

        // Assert
        static::assertTrue($response->isCli());
    }
}
