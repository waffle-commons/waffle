<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use Waffle\Core\YamlParser;
use WaffleTests\AbstractTestCase as TestCase;

class YamlParserTest extends TestCase
{
    private null|string $tempFile = null;

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->tempFile && file_exists($this->tempFile)) {
            unlink($this->tempFile);
            $this->tempFile = null;
        }
    }

    public function testParseFileWithSimpleStructure(): void
    {
        // Arrange
        $content = <<<YAML
        app:
          name: 'WaffleApp'
          env: test
        database:
          host: localhost
        YAML;
        $this->tempFile = $this->createTempFile($content);
        $parser = new YamlParser();

        $expected = [
            'app' => [
                'name' => 'WaffleApp',
                'env' => 'test',
            ],
            'database' => [
                'host' => 'localhost',
            ],
        ];

        // Act
        $result = $parser->parseFile($this->tempFile);

        // Assert
        static::assertSame($expected, $result);
    }

    public function testParseFileWithCommentsAndEmptyLines(): void
    {
        // Arrange
        $content = <<<YAML
        # Application configuration
        app:
          name: WaffleApp

        # Database connection
        database:
          host: 127.0.0.1
          port: 3306
        YAML;
        $this->tempFile = $this->createTempFile($content);
        $parser = new YamlParser();

        $expected = [
            'app' => [
                'name' => 'WaffleApp',
            ],
            'database' => [
                'host' => '127.0.0.1',
                'port' => 3306,
            ],
        ];

        // Act
        $result = $parser->parseFile($this->tempFile);

        // Assert
        static::assertSame($expected, $result);
    }

    public function testParseFileReturnsEmptyArrayForNonexistentFile(): void
    {
        // Arrange
        $parser = new YamlParser();

        // Act
        $result = $parser->parseFile('/non/existent/file.yaml');

        // Assert
        static::assertSame([], $result);
    }

    public function testParseFileWithDeeplyNestedStructure(): void
    {
        // Arrange
        $content = <<<YAML
        level1:
          level2:
            level3:
              key: 'deep_value'
        YAML;
        $this->tempFile = $this->createTempFile($content);
        $parser = new YamlParser();
        $expected = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'key' => 'deep_value',
                    ],
                ],
            ],
        ];

        // Act
        $result = $parser->parseFile($this->tempFile);

        // Assert
        static::assertSame($expected, $result);
    }

    public function testParseFileHandlesEmptyFileGracefully(): void
    {
        // Arrange
        $this->tempFile = $this->createTempFile('');
        $parser = new YamlParser();

        // Act
        $result = $parser->parseFile($this->tempFile);

        // Assert
        static::assertSame([], $result);
    }

    public function testParseFileWithValueContainingSpecialCharacters(): void
    {
        // Arrange
        $content = "url: 'http://example.com?query=1:2'";
        $this->tempFile = $this->createTempFile($content);
        $parser = new YamlParser();
        $expected = ['url' => 'http://example.com?query=1:2'];

        // Act
        $result = $parser->parseFile($this->tempFile);

        // Assert
        static::assertSame($expected, $result);
    }

    public function testParseFileIgnoresInvalidLines(): void
    {
        // Arrange
        $content = <<<YAML
        valid_key: valid_value
        list:
          - just a list item 1
          - just a list item 2
          - just a list item 3
        another_valid_key: another_value
        YAML;
        $this->tempFile = $this->createTempFile($content);
        $parser = new YamlParser();
        $expected = [
            'valid_key' => 'valid_value',
            'list' => [
                'just a list item 1',
                'just a list item 2',
                'just a list item 3',
            ],
            'another_valid_key' => 'another_value',
        ];

        // Act
        $result = $parser->parseFile($this->tempFile);

        // Assert
        static::assertSame($expected, $result);
    }

    private function createTempFile(string $content): string
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'waffle_test_');
        file_put_contents($this->tempFile, $content);
        return $this->tempFile;
    }
}
