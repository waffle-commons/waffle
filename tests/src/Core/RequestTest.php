<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionException;
use Waffle\Abstract\AbstractRequest;
use Waffle\Core\Request;
use Waffle\Core\Response;
use Waffle\Enum\HttpBag;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Controller\TempController;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    /**
     * This test ensures that the Request class can be successfully instantiated
     * and that it correctly extends its abstract parent.
     */
    public function testCanBeInstantiated(): void
    {
        // When: A new Request object is created.
        $request = $this->createRealRequest();

        // Then: It should be an instance of both Request and AbstractRequest.
        static::assertInstanceOf(Request::class, $request);
        static::assertInstanceOf(AbstractRequest::class, $request);
    }

    /**
     * This test verifies that the process() method returns a Response object
     * when a route has been set.
     */
    public function testProcessReturnsResponseWhenRouteIsSet(): void
    {
        // Given: A request object with a configured route.
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
            'classname' => TempController::class,
            'method' => 'test_route',
            'arguments' => ['123'],
            'path' => '/test',
            'name' => 'test_route',
        ];
        $request->setCurrentRoute($routeData);

        // When: The process() method is called.
        $response = $request->process();

        // Then: The method should return an instance of the Response class.
        static::assertInstanceOf(Response::class, $response);
    }

    /**
     * This test ensures that `setCurrentRoute` correctly updates the internal
     * state and maintains a fluent interface.
     * @throws ReflectionException
     */
    public function testSetCurrentRouteSetsPropertyAndReturnsSelf(): void
    {
        // Given: A new Request object.
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
            'classname' => TempController::class,
            'method' => 'list',
            'arguments' => ['123'],
            'path' => '/users',
            'name' => 'user_list',
        ];

        // When: The setCurrentRoute method is called.
        $result = $request->setCurrentRoute($routeData);

        // Then: The method should return the same instance for method chaining.
        static::assertSame($request, $result, 'The method should return its own instance (fluent interface).');

        // And: The internal 'currentRoute' property should be correctly set.
        static::assertSame($routeData, $this->getProtectedRoute($request, 'currentRoute'));
    }

    /**
     * This test verifies that the public properties of the Request object
     * correctly expose the corresponding PHP superglobals.
     *
     * @param HttpBag $property The name of the public property to test.
     * @param array<string, string> $superglobal The superglobal array to simulate.
     */
    #[DataProvider('superglobalProvider')]
    public function testSuperglobalPropertiesAreCorrectlyExposed(HttpBag $property, array $superglobal): void
    {
        // Given: We simulate a superglobal array.
        // This assignment depends on how PHPUnit handles superglobals.
        // In some environments, direct assignment works.
        $gProps = match ($property) {
            HttpBag::QUERY => 'get',
            HttpBag::REQUEST => 'post',
            HttpBag::COOKIES => 'cookie',
            HttpBag::SESSION => 'session',
            HttpBag::ENV => 'env',
            HttpBag::FILES => 'files',
            default => 'server',
        };
        $GLOBALS['_' . strtoupper($gProps)] = $superglobal;
        $globals = [
            'server' => $superglobal,
            'get' => $superglobal,
            'post' => $superglobal,
            'files' => $superglobal,
            'cookie' => $superglobal,
            'session' => $superglobal,
            'request' => $superglobal,
            'env' => $superglobal,
        ];

        // When: A new Request object is created.
        $request = $this->createRealRequest(globals: $globals);
        $request->configure(
            container: $request->container,
            globals: $globals,
        ); // Manually trigger configuration to load superglobals

        // Then: The public property should accurately reflect the superglobal values.
        foreach ($superglobal as $key => $value) {
            static::assertSame($value, $request->bag(key: $property)->get(key: $key));
        }
    }

    /**
     * Provides test cases for each superglobal property.
     *
     * @return array<string, array{0: string, 1: array<string, string>}>
     */
    public static function superglobalProvider(): array
    {
        return [
            'QUERY superglobal' => [HttpBag::QUERY, ['page' => '1']],
            'REQUEST superglobal' => [HttpBag::REQUEST, ['name' => 'John']],
            'COOKIES superglobal' => [HttpBag::COOKIES, ['session_id' => 'abcde']],
            'SERVER superglobal' => [HttpBag::SERVER, ['REQUEST_URI' => '/home']],
            'ENV superglobal' => [HttpBag::ENV, ['APP_ENV' => 'test']],
        ];
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
