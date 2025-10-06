<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Waffle\Trait\RequestTrait;

final class RequestTraitTest extends TestCase
{
    use RequestTrait;

    // @phpstan-ignore missingType.iterableValue
    public static function requestUriProvider(): array
    {
        return [
            'Root URL' => ['/', ['', '']],
            'Simple path' => ['/users/list', ['', 'users', 'list']],
            'Path with query string' => ['/products/view?id=123', ['', 'products', 'view']],
            'Path with trailing slash' => ['/articles/', ['', 'articles', '']],
        ];
    }

    /**
     * @param string $uri
     * @param array{string, string[]} $expected
     * @return void
     */
    #[DataProvider('requestUriProvider')]
    public function testGetRequestUri(string $uri, array $expected): void
    {
        static::assertSame($expected, $this->getRequestUri($uri));
    }

    // @phpstan-ignore missingType.iterableValue
    public static function pathUriProvider(): array
    {
        return [
            'Root path' => ['/', ['', '']],
            'Simple path' => ['/users/list', ['', 'users', 'list']],
            'Path with parameters' => ['/users/{id}/edit', ['', 'users', '{id}', 'edit']],
        ];
    }

    /**
     * @param string $path
     * @param array{string, string[]} $expected
     * @return void
     */
    #[DataProvider('pathUriProvider')]
    public function testGetPathUri(string $path, array $expected): void
    {
        static::assertSame($expected, $this->getPathUri($path));
    }
}
