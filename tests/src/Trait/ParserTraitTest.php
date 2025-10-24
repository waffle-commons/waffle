<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Trait\ParserTrait;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Trait\Helper\TraitParser;

#[CoversTrait(ParserTrait::class)]
final class ParserTraitTest extends TestCase
{
    // Use an anonymous class that uses the trait for testing
    private object $traitObject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new TraitParser();
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function valueProvider(): array
    {
        return [
            // Basic types
            'Plain string' => ['hello world', 'hello world'],
            'String with spaces' => ['  spaces  ', '  spaces  '], // parseValue itself doesn't trim
            'Single quoted string' => ["'quoted'", 'quoted'],
            'Double quoted string' => ['"quoted"', 'quoted'],
            'Boolean true lowercase' => ['true', true],
            'Boolean true uppercase' => ['TRUE', true],
            'Boolean false lowercase' => ['false', false],
            'Boolean false uppercase' => ['FALSE', false],
            'Null tilde' => ['~', null],
            'Null lowercase' => ['null', null],
            'Null uppercase' => ['NULL', null],
            'Empty string' => ['', null], // Empty string is parsed as null in YAML context here
            'Integer' => ['123', 123],
            'Negative integer' => ['-45', -45],
            'Float' => ['123.45', 123.45],
            'Negative float' => ['-0.5', -0.5],
            'Float with zero decimal' => ['10.0', 10.0],
            'Scientific notation' => ['1e3', 1000], // Interpreted as float by is_numeric
            // Edge cases resembling types but should be strings
            'String "true"' => ["'true'", 'true'],
            'String "false"' => ["'false'", 'false'],
            'String "null"' => ["'null'", 'null'],
            'String "123"' => ["'123'", '123'],
            'String "123.45"' => ["'123.45'", '123.45'],
            'String looking like number but not' => ['12e4a', '12e4a'],
            'String with colon' => ['key:value', 'key:value'],
            'String with special chars' => ['!@#$%^&*()', '!@#$%^&*()'],
        ];
    }

    /**
     * @param string $inputValue
     * @param mixed $expectedValue
     */
    #[DataProvider('valueProvider')]
    public function testParseValue(string $inputValue, mixed $expectedValue): void
    {
        static::assertSame($expectedValue, $this->traitObject->callParseValue($inputValue));
    }
}
