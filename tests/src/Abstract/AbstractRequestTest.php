<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionException;
use Waffle\Abstract\AbstractRequest;
use Waffle\Core\Response;
use Waffle\Enum\AppMode;
use Waffle\Enum\HttpBag;
use Waffle\Exception\RouteNotFoundException;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Router\Dummy\DummyController;

#[CoversClass(AbstractRequest::class)]
final class AbstractRequestTest extends TestCase
{
    /**
     * This test verifies that the process() method correctly returns a Response object
     * when a valid route has been set on the request object. It simulates a typical
     * "happy path" scenario in a web context.
     * @throws ReflectionException|RouteNotFoundException
     */
    public function testProcessReturnsResponseWhenRouteIsSet(): void
    {
        $request = $this->createRealRequest();

        $this->setProtectedRoute($request, 'currentRoute', ['path' => '/test']);
        $response = $request->process();

        static::assertInstanceOf(Response::class, $response);
    }

    /**
     * This test ensures that the framework correctly throws a RouteNotFoundException
     * when no route matches the incoming request in a web environment. This is critical
     * for proper 404 error handling.
     */
    public function testProcessThrowsExceptionWhenNoRouteIsFound(): void
    {
        static::expectException(RouteNotFoundException::class);
        static::expectExceptionMessage('Route not found.');

        $request = $this->createRealRequest();
        $request->process();
    }

    /**
     * This test validates a key architectural choice: in a Command-Line Interface (CLI)
     * context, the application should not fail if no route is provided. This allows for
     * CLI commands to be handled differently from web requests.
     */
    public function testProcessDoesNotThrowExceptionInCliMode(): void
    {
        $request = $this->createRealRequest(isCli: AppMode::CLI);

        $response = $request->process();
        static::assertInstanceOf(Response::class, $response);
    }

    /**
     * This test ensures that the `setCurrentRoute` method correctly updates the internal
     * state of the request object and adheres to a fluent interface pattern by returning
     * its own instance.
     * @throws ReflectionException
     */
    public function testSetCurrentRouteSetsPropertyAndReturnsSelf(): void
    {
        $request = $this->createRealRequest();

        /**
         * @var array{
         *       classname: string,
         *       method: non-empty-string,
         *       arguments: array<non-empty-string, string>,
         *       path: string,
         *       name: non-falsy-string
         *   }|null $routeData
         */
        $routeData = [
            'classname' => DummyController::class,
            'method' => 'list',
            'arguments' => [],
            'path' => '/home',
            'name' => 'user_home',
        ];

        $result = $request->setCurrentRoute($routeData);

        static::assertSame($request, $result);
        static::assertSame($routeData, $this->getProtectedRoute($request, 'currentRoute'));
    }

    /**
     * This test verifies that the public properties of the AbstractRequest (e.g., $server, $env)
     * are correctly initialized and expose the corresponding PHP superglobals, as handled
     * by the `configure()` method.
     */
    public function testSuperglobalPropertiesAreCorrectlyExposed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_ENV['APP_ENV'] = 'test';

        $request = $this->createRealRequest(globals: [
            'server' => $_SERVER ?? [],
            'get' => $_GET ?? [],
            'post' => $_POST ?? [],
            'files' => $_FILES ?? [],
            'cookie' => $_COOKIE ?? [],
            'session' => $_SESSION ?? [],
            'request' => $_GET ?? [],
            'env' => $_ENV ?? [],
        ]);

        static::assertSame('GET', $request->bag(key: HttpBag::SERVER)->get(key: 'REQUEST_METHOD'));
        static::assertSame('test', $request->bag(key: HttpBag::ENV)->get(key: 'APP_ENV'));

        unset($_SERVER['REQUEST_METHOD'], $_ENV['APP_ENV']);
    }

    /**
     * Helper method to set the value of a protected property for testing purposes.
     * This allows us to simulate the state of an object without exposing its internal properties publicly.
     * @param string[] $value
     * @throws ReflectionException
     */
    private function setProtectedRoute(object $object, string $property, array $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setValue($object, $value);
    }

    /**
     * Helper method to get the value of a protected property for assertion purposes.
     * @throws ReflectionException
     */
    private function getProtectedRoute(object $object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        return $property->getValue($object);
    }
}
