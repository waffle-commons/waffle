<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\TestCase;
use Waffle\Trait\DotenvTrait;

/**
 * @psalm-suppress UndefinedConstant
 */
final class DotenvTraitTest extends TestCase
{
    use DotenvTrait;

    private string $envFilePath;
    private string $envTestFilePath;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Define paths for temporary .env files within the test directory.
        $this->envFilePath = APP_ROOT . DIRECTORY_SEPARATOR . '.env.temp';
        $this->envTestFilePath = APP_ROOT . DIRECTORY_SEPARATOR . '.env.test.temp';

        // Create a dummy .env file for the test.
        file_put_contents($this->envFilePath, "APP_ENV=test\nTEST_VAR=HelloWaffle");

        // Create a dummy .env.test file.
        file_put_contents($this->envTestFilePath, "APP_ENV=test\nTEST_VAR=HelloWaffleTest");
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Clean up the created files.
        if (file_exists($this->envFilePath)) {
            unlink($this->envFilePath);
        }
        if (file_exists($this->envTestFilePath)) {
            unlink($this->envTestFilePath);
        }

        // Unset environment variables to avoid side effects between tests.
        unset($_ENV['APP_ENV'], $_ENV['TEST_VAR']);
        parent::tearDown();
    }

    public function testLoadEnvLoadsVariables(): void
    {
        // Redefine the path to our temporary file by overriding the trait's loadEnv method.
        $trait = new class () {
            use DotenvTrait {
                DotenvTrait::loadEnv as public traitLoadEnv;
            }

            public function loadEnv(bool $tests = false): void
            {
                // Temporarily rename the temp file to what loadEnv expects.
                rename(APP_ROOT . '/.env.temp', APP_ROOT . '/.env');
                $this->traitLoadEnv($tests);
                rename(APP_ROOT . '/.env', APP_ROOT . '/.env.temp');
            }
        };

        $trait->loadEnv();

        static::assertSame('test', $_ENV['APP_ENV']);
        static::assertSame('HelloWaffle', $_ENV['TEST_VAR']);
    }

    public function testLoadEnvForTestsLoadsTestVariables(): void
    {
        $trait = new class () {
            use DotenvTrait {
                DotenvTrait::loadEnv as public traitLoadEnv;
            }

            public function loadEnv(bool $tests = false): void
            {
                // Temporarily rename the temp file to what loadEnv expects.
                rename(APP_ROOT . '/.env.test.temp', APP_ROOT . '/.env.test');
                $this->traitLoadEnv($tests);
                rename(APP_ROOT . '/.env.test', APP_ROOT . '/.env.test.temp');
            }
        };

        $trait->loadEnv(tests: true);

        static::assertSame('test', $_ENV['APP_ENV']);
        static::assertSame('HelloWaffleTest', $_ENV['TEST_VAR']);
    }
}
