<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionException;
use Waffle\Abstract\AbstractCli;
use Waffle\Core\Response;
use Waffle\Enum\HttpBag;
use WaffleTests\Abstract\Helper\ConcreteTestCli;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Controller\TempController;

#[CoversClass(AbstractCli::class)]
final class AbstractCliTest extends TestCase
{
    /**
     * This test verifies that the process() method consistently returns a Response object,
     * which is the expected behavior for any CLI command execution.
     */
    public function testProcessReturnsResponse(): void
    {
        // Given: A CLI object.
        $cli = new ConcreteTestCli(container: $this->createMockContainer());

        // When: The process() method is called.
        $response = $cli->process();

        // Then: The method should return an instance of the Response class.
        static::assertInstanceOf(Response::class, $response);
    }

    /**
     * This test ensures that the `setCurrentRoute` method correctly updates the internal
     * state of the CLI object and adheres to a fluent interface pattern.
     * @throws ReflectionException
     */
    public function testSetCurrentRouteSetsPropertyAndReturnsSelf(): void
    {
        // Given: A new CLI object.
        $cli = new ConcreteTestCli(container: $this->createMockContainer());
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
            'method' => 'show',
            'arguments' => ['123'],
            'path' => 'app:command',
            'name' => 'app_command',
        ];

        // When: The setCurrentRoute method is called.
        $result = $cli->setCurrentRoute($routeData);

        // Then: The method should return the same instance for method chaining.
        static::assertSame($cli, $result, 'The method should return its own instance (fluent interface).');

        // And: The internal 'currentRoute' property should be correctly set.
        static::assertSame($routeData, $this->getProtectedRoute($cli, 'currentRoute'));
    }

    /**
     * This test verifies that the public properties of the AbstractCli (e.g., $server, $env)
     * are correctly initialized and expose the corresponding PHP superglobals.
     */
    public function testSuperglobalPropertiesAreCorrectlyExposed(): void
    {
        // Given: We simulate a specific server environment for the CLI.
        $serverGlobals = ['PHP_SELF' => 'vendor/bin/phpunit'];
        $envGlobals = ['APP_ENV' => 'test'];

        // When: A new CLI object is created and configured.
        $cli = new ConcreteTestCli(
            container: $this->createMockContainer(),
            globals: [
                'server' => $serverGlobals,
                'env' => $envGlobals,
            ],
        );

        // Then: The public properties should accurately reflect the superglobal values.
        static::assertSame('vendor/bin/phpunit', $cli->bag(key: HttpBag::SERVER)->get(key: 'PHP_SELF'));
        static::assertSame('test', $cli->bag(key: HttpBag::ENV)->get(key: 'APP_ENV'));
    }

    /**
     * Tests the isCli() method.
     */
    public function testIsCliReturnsTrue(): void
    {
        // Given: A CLI object (implicitly in CLI mode by using ConcreteTestCli helper).
        $cli = $this->createRealCli(); // Uses the HttpFactoryTrait helper

        // Then: isCli() should return true.
        static::assertTrue($cli->isCli());
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
