<?php

declare(strict_types=1);

namespace WaffleTests\Attribute;

use PHPUnit\Framework\TestCase;
use Waffle\Attribute\Configuration;

final class ConfigurationTest extends TestCase
{
    private string $controllerDir;
    private string $serviceDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Create dummy directories to simulate a real application structure.
        /** @var string $root */
        $root = APP_ROOT;
        $this->controllerDir = $root . DIRECTORY_SEPARATOR . 'app/Controller';
        $this->serviceDir = $root . DIRECTORY_SEPARATOR . 'app/Service';
        mkdir($this->controllerDir, 0o777, true);
        mkdir($this->serviceDir, 0o777, true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Clean up the dummy directories after the test.
        if (is_dir($this->controllerDir)) {
            rmdir($this->controllerDir);
        }
        if (is_dir($this->serviceDir)) {
            rmdir($this->serviceDir);
        }
        // Clean up parent 'app' directory if it's empty
        /** @var string $root */
        $root = APP_ROOT;
        if (is_dir($root . DIRECTORY_SEPARATOR . 'app')) {
            // TODO(@supa-chayajin): Handle error permissions
            rmdir($root . DIRECTORY_SEPARATOR . 'app');
        }
        parent::tearDown();
    }

    public function testConstructorCorrectlyResolvesPaths(): void
    {
        // --- Execution ---
        // Instantiate the Configuration attribute.
        $config = new Configuration();

        // --- Assertions ---
        // We use Reflection to access the protected properties and verify their values.
        $reflection = new \ReflectionObject($config);

        $controllerDirProp = $reflection->getProperty('controllerDir');
        static::assertSame($this->controllerDir, $controllerDirProp->getValue($config));

        $serviceDirProp = $reflection->getProperty('serviceDir');
        static::assertSame($this->serviceDir, $serviceDirProp->getValue($config));

        $securityLevelProp = $reflection->getProperty('securityLevel');
        static::assertSame(10, $securityLevelProp->getValue($config), 'Default security level should be 10.');
    }

    public function testConstructorHandlesNonExistentPaths(): void
    {
        // --- Test Condition ---
        // We ensure the directories do not exist before running the test.
        rmdir($this->controllerDir);
        rmdir($this->serviceDir);

        // --- Execution ---
        $config = new Configuration();

        // --- Assertions ---
        // If paths are invalid, realpath() returns false, which should be stored in the properties.
        $reflection = new \ReflectionObject($config);

        $controllerDirProp = $reflection->getProperty('controllerDir');
        static::assertEmpty($controllerDirProp->getValue($config));

        $serviceDirProp = $reflection->getProperty('serviceDir');
        static::assertEmpty($serviceDirProp->getValue($config));
    }
}
