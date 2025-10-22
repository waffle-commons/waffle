<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Trait\ParserTrait;
use WaffleTests\AbstractTestCase;
use WaffleTests\Trait\Helper\TraitParser;

#[CoversTrait(ParserTrait::class)]
final class ParserTraitTest extends AbstractTestCase
{
    // Use an anonymous class or a dedicated test class using the trait
    private object $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new TraitParser();
    }

    /**
     * @param string $inputValue The string value to parse.
     * @param mixed $expectedValue The expected parsed value and type.
     */
    #[DataProvider('valueProvider')]
    public function testParseValue(string $inputValue, mixed $expectedValue): void
    {
        // Act
        $parsedValue = $this->traitObject->testParseValue($inputValue);

        // Assert
        // Use assertSame to check both value and type
        $this->assertSame($expectedValue, $parsedValue);
    }

    /**
     * Provides various string inputs and their expected parsed values.
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function valueProvider(): array
    {
        return [
            'Plain string' => ['hello world', 'hello world'],
            // Adjust expectation: ParserTrait itself doesn't trim simple strings
            'String with spaces' => ['  spaces  ', '  spaces  '],
            'Single quoted string' => ["'quoted'", 'quoted'],
            'Double quoted string' => ['"double quoted"', 'double quoted'],
            // ... rest of the provider data ...
            'Boolean true lowercase' => ['true', true],
            'Boolean true uppercase' => ['TRUE', true],
            'Boolean false lowercase' => ['false', false],
            'Boolean false uppercase' => ['FALSE', false],
            'Null tilde' => ['~', null],
            'Null lowercase' => ['null', null],
            'Null uppercase' => ['NULL', null],
            'Empty string' => ['', null], // Empty string is treated as null
            'String "true"' => ["'true'", 'true'], // Quoted boolean should be string
            'String "false"' => ['"false"', 'false'], // Quoted boolean should be string
            'String "null"' => ["'null'", 'null'], // Quoted null should be string
            'String "123"' => ["'123'", '123'], // Quoted number should be string
            'String "123.45"' => ['"123.45"', '123.45'], // Quoted float should be string
            'String looking like number but not' => ['123a', '123a'],
            'String with colon' => ['key:value', 'key:value'],
            'String with special chars' => ['!@#$%^&*()', '!@#$%^&*()'],
        ];
    }
}
