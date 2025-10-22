<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass; // Added for testing protected method
use ReflectionObject;
use Waffle\Core\Config; // Added use statement
use Waffle\Core\System;
use Waffle\Enum\Failsafe;
use Waffle\Exception\InvalidConfigurationException; // Added use statement
use Waffle\Interface\YamlParserInterface;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(Config::class)] // Added CoversClass
class ConfigTest extends TestCase
{
    private System $systemMock;
    private YamlParserInterface $yamlParserMock;
    private null|string $tempYamlFileBool = null; // For bool test
    private null|string $tempYamlFileArray = null; // For array test
    private null|string $tempYamlFileEnv = null; // For env test
    private array $tempFilesCreated = []; // Keep track of temp files

    #[\Override]
    protected function setUp(): void
    {
        $this->systemMock = $this->createMock(System::class);
        $this->yamlParserMock = $this->createMock(YamlParserInterface::class);

        parent::setUp(); // Creates the test config directory and default app.yaml

        $yamlContentBool = <<<YAML
        app:
          feature_enabled: true
          another_feature: false
          not_a_bool: 'maybe'
        YAML;
        $this->tempYamlFileBool = $this->testConfigDir . '/app_test_bool.yaml';
        file_put_contents($this->tempYamlFileBool, $yamlContentBool);

        $yamlContentArray = <<<YAML
        database:
          connections:
            - mysql
            - pgsql
          not_an_array: 'just_string'
        YAML;
        $this->tempYamlFileArray = $this->testConfigDir . '/app_test_array.yaml';
        file_put_contents($this->tempYamlFileArray, $yamlContentArray);

        $yamlContentEnv = <<<YAML
        service:
          api_key: '%env(TEST_API_KEY)%'
          url: 'https://example.com'
          missing_var: '%env(NON_EXISTENT_VAR)%'
          nested:
            value: '%env(NESTED_TEST_VAR)%'
        YAML;
        $this->tempYamlFileEnv = $this->testConfigDir . '/app_test_env.yaml';
        file_put_contents($this->tempYamlFileEnv, $yamlContentEnv);
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Clean up additional yaml files
        if ($this->tempYamlFileBool && file_exists($this->tempYamlFileBool)) {
            unlink($this->tempYamlFileBool);
            $this->tempYamlFileBool = null;
        }
        if ($this->tempYamlFileArray && file_exists($this->tempYamlFileArray)) {
            unlink($this->tempYamlFileArray);
            $this->tempYamlFileArray = null;
        }
        if ($this->tempYamlFileEnv && file_exists($this->tempYamlFileEnv)) {
            unlink($this->tempYamlFileEnv);
            $this->tempYamlFileEnv = null;
        }
        // Clean up any other temp files created directly in tests
        foreach ($this->tempFilesCreated as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testFailsafeConfigIsLoaded(): void
    {
        // Act
        $config = $this->createAndGetConfig(failsafe: Failsafe::ENABLED);

        // Assert
        // Check a key known to be missing in failsafe defaults
        static::assertNull($config->getString(key: 'waffle.paths.services'));
        // Check a key known to exist in failsafe defaults
        static::assertSame(1, $config->getInt(key: 'waffle.security.level'));
    }

    public function testGetReturnsCorrectValueForExistingKey(): void
    {
        // Act
        // Uses the default app.yaml created by createTestConfigFile in AbstractTestCase
        $config = $this->createAndGetConfig();

        // Assert
        static::assertSame(10, $config->getInt(key: 'waffle.security.level'));
        static::assertSame('tests/src/Helper/Controller', $config->getString(key: 'waffle.paths.controllers'));
        static::assertSame('tests/src/Helper/Service', $config->getString(key: 'waffle.paths.services'));
    }

    public function testGetReturnsDefaultValueForNonexistentKey(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        $getDefault = $config->getString(
            key: 'app.nonexistent',
            default: 'default_value',
        );
        static::assertSame('default_value', $getDefault);
    }

    public function testGetReturnsNullForNonexistentKeyWhenNoDefaultIsProvided(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        static::assertNull($config->getString(key: 'app.nonexistent'));
    }

    public function testGetIntThrowsExceptionForInvalidType(): void
    {
        static::expectException(InvalidConfigurationException::class);
        static::expectExceptionMessage(
            'Configuration key "waffle.paths.controllers" expects type "int", but got "string".',
        );

        // Define unique environment and filename
        $envName = 'test_invalid_int';
        $invalidYamlFileName = 'app_' . $envName . '.yaml';
        $invalidYamlContent = "waffle:\n  paths:\n    controllers: 'not-an-int'\n";
        $filePath = $this->testConfigDir . DIRECTORY_SEPARATOR . $invalidYamlFileName;
        file_put_contents($filePath, $invalidYamlContent);
        $this->tempFilesCreated[] = $filePath; // Track for cleanup

        // Create Config directly using the temp file
        $config = new Config(
            configDir: $this->testConfigDir,
            environment: $envName,
            failsafe: Failsafe::DISABLED, // Ensure it tries to load
        );

        $config->getInt('waffle.paths.controllers'); // Trying to get a string as int
    }

    public function testGetStringThrowsExceptionForInvalidType(): void
    {
        static::expectException(InvalidConfigurationException::class);
        static::expectExceptionMessage(
            'Configuration key "waffle.security.level" expects type "string", but got "integer".',
        );

        // Define unique environment and filename
        $envName = 'test_invalid_string';
        $invalidYamlFileName = 'app_' . $envName . '.yaml';
        $invalidYamlContent = "waffle:\n  security:\n    level: 123\n"; // Integer instead of expected string
        $filePath = $this->testConfigDir . DIRECTORY_SEPARATOR . $invalidYamlFileName;
        file_put_contents($filePath, $invalidYamlContent);
        $this->tempFilesCreated[] = $filePath; // Track for cleanup

        // Create Config directly using the temp file
        $config = new Config(
            configDir: $this->testConfigDir,
            environment: $envName,
            failsafe: Failsafe::DISABLED,
        );

        $config->getString('waffle.security.level'); // Trying to get an int as string
    }

    // --- End Exception Tests ---

    // --- Added Tests for getArray / getBool ---
    public function testGetArrayReturnsCorrectValue(): void
    {
        $config = new Config($this->testConfigDir, 'test_array'); // Loads app_test_array.yaml
        $expected = ['mysql', 'pgsql'];
        static::assertSame($expected, $config->getArray('database.connections'));
    }

    public function testGetArrayReturnsDefaultValue(): void
    {
        $config = new Config($this->testConfigDir, 'test_array');
        $default = ['default'];
        static::assertSame($default, $config->getArray('database.nonexistent', $default));
        static::assertNull($config->getArray('database.nonexistent')); // Without default
    }

    public function testGetArrayThrowsExceptionForInvalidType(): void
    {
        static::expectException(InvalidConfigurationException::class);
        static::expectExceptionMessage(
            'Configuration key "database.not_an_array" expects type "array", but got "string".',
        );

        $config = new Config($this->testConfigDir, 'test_array');
        $config->getArray('database.not_an_array');
    }

    public function testGetBoolReturnsCorrectValue(): void
    {
        $config = new Config($this->testConfigDir, 'test_bool'); // Loads app_test_bool.yaml
        static::assertTrue($config->getBool('app.feature_enabled'));
        static::assertFalse($config->getBool('app.another_feature'));
    }

    public function testGetBoolReturnsDefaultValue(): void
    {
        $config = new Config($this->testConfigDir, 'test_bool');
        static::assertTrue($config->getBool('app.nonexistent_bool', true)); // Default true
        static::assertFalse($config->getBool('app.nonexistent_bool')); // Default false (method default)
        static::assertFalse($config->getBool('app.nonexistent_bool', false)); // Explicit default false
    }

    public function testGetBoolThrowsExceptionForInvalidType(): void
    {
        static::expectException(InvalidConfigurationException::class);
        // Note: The error message was corrected to "bool"
        static::expectExceptionMessage('Configuration key "app.not_a_bool" expects type "boolean", but got "string".');

        $config = new Config($this->testConfigDir, 'test_bool');
        $config->getBool('app.not_a_bool');
    }

    // --- End Added Tests for getArray / getBool ---

    public function testLoadHandlesNonexistentConfigFileGracefully(): void
    {
        // Act
        // Use a non-existent environment to ensure no app_*.yaml is found
        $config = new Config($this->testConfigDir, 'nonexistent_env');

        // Assert
        static::assertNull($config->getString(key: 'anything')); // Assuming app.yaml also doesn't exist or is empty
    }

    public function testProtectedGetMethodRetrievesValue(): void
    {
        $config = $this->createAndGetConfig(); // Uses default app.yaml

        // Use Reflection to call the protected get method
        $reflection = new ReflectionClass(Config::class);
        $method = $reflection->getMethod('get');

        // Call protected method 'get'
        $value = $method->invoke($config, 'waffle.security.level');
        static::assertSame(10, $value);

        $nestedValue = $method->invoke($config, 'waffle.paths.controllers');
        static::assertSame('tests/src/Helper/Controller', $nestedValue);

        $nonExistentValue = $method->invoke($config, 'app.nonexistent.key');
        static::assertNull($nonExistentValue);
    }

    public function testResolveEnvPlaceholders(): void
    {
        // Set environment variables for the test
        putenv('TEST_API_KEY=abcdef12345');
        putenv('NESTED_TEST_VAR=nested_value');
        // NON_EXISTENT_VAR is intentionally not set

        $config = new Config($this->testConfigDir, 'test_env'); // Loads app_test_env.yaml

        // Assert that placeholders were replaced
        static::assertSame('abcdef12345', $config->getString('service.api_key'));
        static::assertSame('https://example.com', $config->getString('service.url')); // Unchanged
        static::assertSame('nested_value', $config->getString('service.nested.value'));

        // Assert that a missing env variable results in null
        static::assertNull($config->getString('service.missing_var'));

        // Clean up env variables
        putenv('TEST_API_KEY');
        putenv('NESTED_TEST_VAR');
    }
}
