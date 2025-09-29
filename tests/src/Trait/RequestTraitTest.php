<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Waffle\Trait\RequestTrait;

class RequestTraitTest extends TestCase
{
    use RequestTrait;

    public static function requestUriProvider(): array
    {
        return [
            'Root URL' => ['/', ['', '']],
            'Simple path' => ['/users/list', ['', 'users', 'list']],
            'Path with query string' => ['/products/view?id=123', ['', 'products', 'view']],
            'Path with trailing slash' => ['/articles/', ['', 'articles', '']],
        ];
    }

    #[DataProvider('requestUriProvider')]
    public function testGetRequestUri(string $uri, array $expected): void
    {
        $this->assertSame($expected, $this->getRequestUri($uri));
    }

    public static function pathUriProvider(): array
    {
        return [
            'Root path' => ['/', ['', '']],
            'Simple path' => ['/users/list', ['', 'users', 'list']],
            'Path with parameters' => ['/users/{id}/edit', ['', 'users', '{id}', 'edit']],
        ];
    }

    #[DataProvider('pathUriProvider')]
    public function testGetPathUri(string $path, array $expected): void
    {
        $this->assertSame($expected, $this->getPathUri($path));
    }
}
