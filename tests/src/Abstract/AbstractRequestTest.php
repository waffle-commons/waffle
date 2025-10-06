<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Waffle\Abstract\AbstractRequest;
use Waffle\Core\Response;
use Waffle\Exception\RouteNotFoundException;
use WaffleTests\Abstract\Helper\ConcreteTestRequest;
use WaffleTests\Router\Dummy\DummyController;

#[CoversClass(AbstractRequest::class)]
final class AbstractRequestTest extends TestCase
{
    /**
     * This test verifies that the process() method correctly returns a Response object
     * when a valid route has been set on the request object. It simulates a typical
     * "happy path" scenario in a web context.
     * @throws ReflectionException
     */
    public function testProcessReturnsResponseWhenRouteIsSet(): void
    {
        // Given: A request object configured for a web environment.
        $request = new ConcreteTestRequest();
        $request->configure(cli: false);

        // When: A valid route is set on the request (simulating a successful match).
        $this->setProtectedRoute($request, 'currentRoute', ['path' => '/test']);
        $response = $request->process();

        // Then: The process() method should return an instance of the Response class.
        static::assertInstanceOf(Response::class, $response);
    }

    /**
     * This test ensures that the framework correctly throws a RouteNotFoundException
     * when no route matches the incoming request in a web environment. This is critical
     * for proper 404 error handling.
     */
    public function testProcessThrowsExceptionWhenNoRouteIsFound(): void
    {
        // Expect: The specific RouteNotFoundException to be thrown.
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route not found.');

        // Given: A request object for a web environment with no matching route.
        $request = new ConcreteTestRequest();
        $request->configure(cli: false);

        // When: The process() method is called without a currentRoute.
        $request->process();
    }

    /**
     * This test validates a key architectural choice: in a Command-Line Interface (CLI)
     * context, the application should not fail if no route is provided. This allows for
     * CLI commands to be handled differently from web requests.
     */
    public function testProcessDoesNotThrowExceptionInCliMode(): void
    {
        // Given: A request object configured for a CLI environment.
        $request = new ConcreteTestRequest();
        $request->configure(cli: true);

        // When: The process() method is called without a route.
        $response = $request->process();

        // Then: The method should still return a Response object without throwing an exception.
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
        // Given: A new request object.
        $request = new ConcreteTestRequest();
        /**
         * @var array{
         *       classname: string,
         *       method: non-empty-string,
         *       arguments: array<non-empty-string, string>,
         *       path: string,
         *       name: non-falsy-string
         *   }|null $routeData
         * @phpstan-ignore varTag.nativeType
         */
        $routeData = [
            'classname' => DummyController::class,
            'method' => 'list',
            'arguments' => ['123'],
            'path' => '/home',
            'name' => 'user_hole',
        ];

        // When: The setCurrentRoute method is called.
        $result = $request->setCurrentRoute($routeData);

        // Then: The method should return the same instance for method chaining.
        static::assertSame($request, $result, 'The method should return its own instance (fluent interface).');

        // And: The internal 'currentRoute' property should be correctly set.
        static::assertSame($routeData, $this->getProtectedRoute($request, 'currentRoute'));
    }

    /**
     * This test verifies that the public properties of the AbstractRequest (e.g., $server, $env)
     * are correctly initialized and expose the corresponding PHP superglobals, as handled
     * by the `configure()` method.
     */
    public function testSuperglobalPropertiesAreCorrectlyExposed(): void
    {
        // Given: We simulate a specific server environment.
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_ENV['APP_ENV'] = 'test';

        // When: A new request object is created and configured.
        $request = new ConcreteTestRequest();
        $request->configure(cli: false);

        // Then: The public properties should accurately reflect the superglobal values.
        static::assertSame('GET', $request->server['REQUEST_METHOD']);
        static::assertSame('test', $request->env['APP_ENV']);

        // Cleanup the superglobals to avoid side effects in other tests.
        unset($_SERVER['REQUEST_METHOD'], $_ENV['APP_ENV']);
    }

    /**
     * Helper method to set the value of a protected property for testing purposes.
     * This allows us to simulate the state of an object without exposing its internal properties publicly.
     *
     * @throws ReflectionException
     *
     * @param string[] $value
     *
     * @psalm-param array{path: '/test'} $value
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
