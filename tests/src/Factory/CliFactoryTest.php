<?php

declare(strict_types=1);

namespace WaffleTests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Core\Cli;
use Waffle\Enum\AppMode;
use Waffle\Enum\HttpBag;
use Waffle\Factory\CliFactory;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(CliFactory::class)]
final class CliFactoryTest extends TestCase
{
    private array $originalServer;
    private array $originalEnv;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Backup superglobals
        $this->originalServer = $_SERVER;
        $this->originalEnv = $_ENV;
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Restore superglobals
        $_SERVER = $this->originalServer;
        $_ENV = $this->originalEnv;
        parent::tearDown();
    }

    public function testCreateFromGlobalsCreatesCliInstance(): void
    {
        // Arrange
        $_SERVER['PHP_SELF'] = '/usr/bin/waffle-cli';
        $_ENV['APP_ENV'] = 'test';
        $container = $this->createRealContainer(); // Use helper to get a real container
        $factory = new CliFactory();

        // Act
        $cli = $factory->createFromGlobals($container);

        // Assert
        static::assertInstanceOf(Cli::class, $cli);
        static::assertSame(AppMode::WEB, $cli->cli); // Default mode for factory is WEB, needs adjustment if CLI specific logic is added
        static::assertSame('/usr/bin/waffle-cli', $cli->bag(HttpBag::SERVER)->get('PHP_SELF'));
        static::assertSame('test', $cli->bag(HttpBag::ENV)->get('APP_ENV'));
        static::assertSame($container, $cli->container);
    }

    public function testCreateFromGlobalsHandlesEmptySuperglobals(): void
    {
        // Arrange
        // Unset common keys to simulate an empty environment
        unset($_SERVER['PHP_SELF']);
        unset($_ENV['APP_ENV']);
        $_SERVER = []; // Ensure arrays are empty
        $_ENV = [];
        $container = $this->createRealContainer();
        $factory = new CliFactory();

        // Act
        $cli = $factory->createFromGlobals($container);

        // Assert
        static::assertInstanceOf(Cli::class, $cli);
        static::assertNull($cli->bag(HttpBag::SERVER)->get('PHP_SELF')); // Should return null (default)
        static::assertNull($cli->bag(HttpBag::ENV)->get('APP_ENV'));
    }
}
