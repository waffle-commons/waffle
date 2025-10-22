<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use Waffle\Core\System;
use Waffle\Enum\Failsafe;
use Waffle\Interface\YamlParserInterface;
use WaffleTests\AbstractTestCase as TestCase;

class ConfigTest extends TestCase
{
    private System $systemMock;
    private YamlParserInterface $yamlParserMock;

    #[\Override]
    protected function setUp(): void
    {
        $this->systemMock = $this->createMock(System::class);
        $this->yamlParserMock = $this->createMock(YamlParserInterface::class);
        parent::setUp();
    }

    public function testFailsafeConfigIsLoaded(): void
    {
        // Act
        $config = $this->createAndGetConfig(failsafe: Failsafe::ENABLED);

        // Assert
        static::assertNull($config->getString(key: 'waffle.paths.services'));
    }

    public function testGetReturnsCorrectValueForExistingKey(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        static::assertSame(10, $config->getInt(key: 'waffle.security.level'));
        static::assertSame('tests/src/Helper', $config->getString(key: 'waffle.paths.controllers'));
        static::assertSame('tests/src/Helper', $config->getString(key: 'waffle.paths.services'));
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

    public function testLoadHandlesNonexistentConfigFileGracefully(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        static::assertNull($config->getString(key: 'anything'));
    }
}
