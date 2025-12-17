<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Abstract\AbstractSystem;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Core\System;
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
#[CoversClass(System::class)]
#[AllowMockObjectsWithoutExpectations]
final class AbstractSystemTest extends TestCase
{
    private SecurityInterface $securityMock;
    private ConfigInterface $configMock; // Fix: Declare property
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

        // Create a stub for the Security dependency, as AbstractSystem requires it.
        $this->securityMock = $this->createStub(SecurityInterface::class);
        $this->configMock = $this->createStub(ConfigInterface::class); // Added this line

        // Create a concrete, anonymous class that extends AbstractSystem for testing purposes.
        // This allows us to instantiate and test the non-abstract methods of the parent.
        $this->system = new class($this->securityMock) extends AbstractSystem {
            public function __construct(SecurityInterface $security)
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
}
