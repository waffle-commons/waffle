<?php

declare(strict_types=1);

namespace WaffleTests\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Http\ParameterBag;
use WaffleTests\AbstractTestCase;

#[CoversClass(ParameterBag::class)]
final class ParameterBagTest extends AbstractTestCase
{
    /**
     * @param array<string, mixed> $initialData
     */
    #[DataProvider('parameterProvider')]
    public function testAllReturnsAllParameters(
        array $initialData,
        string $_keyToGet,
        mixed $_expectedValue,
        bool $_expectedResult
    ): void {
        // Arrange
        $bag = new ParameterBag($initialData);

        // Act
        $all = $bag->all();

        // Assert
        $this->assertSame($initialData, $all);
    }

    /**
     * @param array<string, mixed> $initialData
     * @param string $keyToGet
     * @param mixed $expectedValue
     * @param bool $_expectedResult
     */
    #[DataProvider('parameterProvider')]
    public function testGetReturnsCorrectValueForExistingKey(
        array $initialData,
        string $keyToGet,
        mixed $expectedValue,
        bool $_expectedResult
    ): void {
        // Arrange
        $bag = new ParameterBag($initialData);

        // Act
        $value = $bag->get($keyToGet);

        // Assert
        $this->assertSame($expectedValue, $value);
    }

    /**
     * @param array<string, mixed> $initialData
     */
    #[DataProvider('parameterProvider')]
    public function testGetReturnsDefaultValueForNonExistingKey(
        array $initialData,
        string $_keyToGet,
        mixed $_expectedValue,
        bool $_expectedResult
    ): void {
        // Arrange
        $bag = new ParameterBag($initialData);
        $defaultValue = 'default_fallback';

        // Act
        $value = $bag->get('non_existent_key', $defaultValue);

        // Assert
        $this->assertSame($defaultValue, $value);
    }

    /**
     * @param array<string, mixed> $initialData
     */
    #[DataProvider('parameterProvider')]
    public function testGetReturnsNullForNonExistingKeyWithoutDefault(
        array $initialData,
        string $_keyToGet,
        mixed $_expectedValue,
        bool $_expectedResult
    ): void {
        // Arrange
        $bag = new ParameterBag($initialData);

        // Act
        $value = $bag->get('another_non_existent_key');

        // Assert
        $this->assertNull($value);
    }

    /**
     * @param array<string, mixed> $initialData
     * @param string $keyToCheck
     * @param mixed $_expectedValue
     * @param bool $expectedResult
     */
    #[DataProvider('parameterProvider')]
    public function testHasReturnsCorrectBoolean(
        array $initialData,
        string $keyToCheck,
        mixed $_expectedValue,
        bool $expectedResult
    ): void {
        // Arrange
        $bag = new ParameterBag($initialData);

        // Act
        $hasKey = $bag->has($keyToCheck);
        $hasNonExistent = $bag->has('key_that_does_not_exist');

        // Assert
        $this->assertSame($expectedResult, $hasKey);
        $this->assertFalse($hasNonExistent);
    }

    /**
     * Provides sample data for the tests.
     * @return array<string, array{0: array<string, mixed>, 1?: string, 2?: mixed, 3?:bool}>
     */
    public static function parameterProvider(): array
    {
        return [
            'Simple Data' => [
                ['name' => 'Waffle', 'version' => 1.0, 'active' => true],
                'name', // keyToGet / keyToCheck
                'Waffle', // expectedValue
                true // expectedResult for has()
            ],
            'Empty Data' => [
                [],
                'any_key', // keyToGet / keyToCheck (will use default or null)
                null, // expectedValue (will use default or null)
                false // expectedResult for has()
            ],
            'Numeric Key' => [ // Although less common for HTTP params, test it
                ['key1' => 'value1'],
                'key1', // keyToGet / keyToCheck
                'value1', // expectedValue
                true // expectedResult for has()
            ],
            'Null Value' => [
                ['nullable_key' => null],
                'nullable_key', // keyToGet / keyToCheck
                null, // expectedValue
                true // expectedResult for has() - key exists even if value is null
            ],
        ];
    }
}
