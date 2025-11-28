<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Abstract\AbstractSystem;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Security\Security;
use Waffle\Core\System;
use Waffle\Router\Router;
use WaffleTests\AbstractTestCase as TestCase;

/**
 * This test class is dedicated to validating the AbstractSystem class, which serves
 * as a foundational component of the framework's architecture. Since it's an abstract
 * class, we test its concrete behaviors by creating an anonymous class that extends it.
 *
 * The tests within this class ensure three key aspects:
 * 1.  Correct dependency injection and property initialization through the constructor.
 * 2.  The initial state of properties to prevent unexpected null values.
 * 3.  The correct functionality of public methods like `registerRouter`.
 */
#[CoversClass(AbstractSystem::class)]
final class AbstractSystemTest extends TestCase
{
    private Security $securityMock;
    private AbstractSystem $system;

    /**
     * The setUp method is executed before each test in this class. It handles the
     * repetitive setup logic, ensuring a clean and consistent test environment.
     *
     * Here, it creates a mock for the Security dependency and instantiates a concrete
     * version of our AbstractSystem using an anonymous class. This follows the DRY
     * (Don't Repeat Yourself) principle and makes the individual tests cleaner and
     * more focused on their specific assertions.
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock for the Security dependency, as AbstractSystem requires it.
        $this->securityMock = $this->createMock(Security::class);

        // Create a concrete, anonymous class that extends AbstractSystem for testing purposes.
        // This allows us to instantiate and test the non-abstract methods of the parent.
        $this->system = new class($this->securityMock) extends AbstractSystem {
            public function __construct(Security $security)
            {
                // We must call the parent constructor if it exists, but here we are
                // setting the protected property directly as per the abstract class's design.
                $this->security = $security;
                $this->config = new \stdClass();
            }

            // A minimal implementation of the abstract boot method is required to
            // instantiate the class, even if it's not used in all tests.
            #[\Override]
            public function boot(KernelInterface $kernel): \Waffle\Commons\Contracts\System\SystemInterface
            {
                return $this;
            }
        };
    }

    /**
     * This test verifies that the constructor of the AbstractSystem (via our anonymous
     * class) correctly receives and assigns its dependencies. It ensures that the
     * internal state of the object is correctly initialized right after instantiation.
     */
    public function testConstructorSetsDependenciesCorrectly(): void
    {
        // Use reflection to access the protected 'security' property. This is a
        // "white-box" testing technique necessary for verifying the internal state
        // of an object without exposing its properties publicly.
        $reflector = new \ReflectionObject($this->system);
        $securityProperty = $reflector->getProperty('security');

        // Assert that the 'security' property holds the exact same mock object
        // we passed into the constructor.
        static::assertSame($this->securityMock, $securityProperty->getValue($this->system));
    }

    /**
     * This test validates the initial state of the `router` property. It's crucial
     * to ensure that properties are in a predictable state (in this case, null)
     * before they are explicitly set. This prevents bugs related to uninitialized
     * or "stale" state.
     */
    public function testRouterPropertyIsNullByDefault(): void
    {
        // Use reflection to access the protected 'router' property.
        $reflector = new \ReflectionObject($this->system);
        $routerProperty = $reflector->getProperty('router');

        // Assert that the router property is null immediately after the object
        // is created, before `registerRouter` has been called.
        static::assertNull($routerProperty->getValue($this->system));
    }

    /**
     * This test covers the main public method of the AbstractSystem class: `registerRouter`.
     * It ensures that the method correctly assigns the provided Router object to its
     * corresponding internal property, fulfilling its primary responsibility.
     */
    public function testRegisterRouterSetsRouterProperty(): void
    {
        // 1. Setup
        // We cannot mock the Router class directly because it is declared as "final".
        // PHPUnit cannot create a mock for a final class. Instead, we must create
        // a real instance. This requires providing its own dependencies.
        $routerSystemMock = $this->createMock(System::class);
        $router = new Router(
            directory: false,
            system: $routerSystemMock,
        );

        // 2. Action
        // Call the method we want to test.
        $this->system->registerRouter($router);

        // 3. Assertions
        // Use reflection to access the protected 'router' property for verification.
        $reflector = new \ReflectionObject($this->system);
        $routerProperty = $reflector->getProperty('router');

        // Assert that the property now holds the exact Router instance we passed to the method.
        static::assertSame($router, $routerProperty->getValue($this->system));
    }

    public function testGetRouterReturnsNullByDefault(): void
    {
        static::assertNull($this->system->getRouter());
    }

    public function testGetRouterReturnsRegisteredRouter(): void
    {
        $routerSystemMock = $this->createMock(System::class);
        $router = new Router(
            directory: false,
            system: $routerSystemMock,
        );

        $this->system->registerRouter($router);

        static::assertSame($router, $this->system->getRouter());
    }
}
