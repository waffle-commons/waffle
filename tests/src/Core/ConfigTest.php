<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use Waffle\Core\System;
use Waffle\Interface\YamlParserInterface;
use WaffleTests\TestCase;

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
        $config = $this->createAndGetConfig(failsafe: true);

        // Assert
        static::assertNull($config->get('waffle.paths.services'));
    }

    public function testGetReturnsCorrectValueForExistingKey(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        static::assertSame('tests/src/Helper', $config->get('waffle.paths.controllers'));
        static::assertSame('tests/src/Helper', $config->get('waffle.paths.services'));
    }

    public function testGetReturnsDefaultValueForNonexistentKey(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        static::assertSame('default_value', $config->get('app.nonexistent', 'default_value'));
    }

    public function testGetReturnsNullForNonexistentKeyWhenNoDefaultIsProvided(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        static::assertNull($config->get('app.nonexistent'));
    }

    public function testLoadHandlesNonexistentConfigFileGracefully(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        static::assertNull($config->get('anything'));
    }
}
