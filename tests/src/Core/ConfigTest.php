<?php

namespace WaffleTests\Core;

use Waffle\Core\System;
use Waffle\Interface\YamlParserInterface;
use WaffleTests\TestCase;

class ConfigTest extends TestCase
{
    private System $systemMock;
    private YamlParserInterface $yamlParserMock;

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
        $this->assertNull($config->get('waffle.paths.services'));
    }

    public function testGetReturnsCorrectValueForExistingKey(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        $this->assertSame('tests/src/Helper', $config->get('waffle.paths.controllers'));
        $this->assertSame('tests/src/Helper', $config->get('waffle.paths.services'));
    }

    public function testGetReturnsDefaultValueForNonexistentKey(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        $this->assertSame('default_value', $config->get('app.nonexistent', 'default_value'));
    }

    public function testGetReturnsNullForNonexistentKeyWhenNoDefaultIsProvided(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        $this->assertNull($config->get('app.nonexistent'));
    }

    public function testLoadHandlesNonexistentConfigFileGracefully(): void
    {
        // Act
        $config = $this->createAndGetConfig();

        // Assert
        $this->assertNull($config->get('anything'));
    }
}
