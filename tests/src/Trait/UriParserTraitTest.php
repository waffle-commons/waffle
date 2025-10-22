<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Trait\UriParserTrait;
use WaffleTests\AbstractTestCase;

#[CoversTrait(UriParserTrait::class)]
final class UriParserTraitTest extends AbstractTestCase
{
    // Use an anonymous class or a dedicated test class using the trait
    private object $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new class {
            use UriParserTrait;

            // Expose protected methods publicly for testing
            public function testGetPathUri(string $path): array
            {
                return $this->getPathUri($path);
            }

            public function testGetRequestUri(string $uri): array
            {
                return $this->getRequestUri($uri);
            }
        };
    }

    /**
     * @param string $pathInput The input path string.
     * @param array<int, string> $expectedSegments The expected array of segments.
     */
    #[DataProvider('pathUriProvider')]
    public function testGetPathUri(string $pathInput, array $expectedSegments): void
    {
        // Act
        $segments = $this->traitObject->testGetPathUri($pathInput);

        // Assert
        $this->assertSame($expectedSegments, $segments);
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function pathUriProvider(): array
    {
        // Adjusted expectations based on preg_split('#/#', ..., PREG_SPLIT_NO_EMPTY)
        return [
            'Root path' => ['/', ['']], // Root path special case
            'Simple path' => ['/users/list', ['users', 'list']],
            'Path with trailing slash' => ['/articles/', ['articles']], // Trailing empty segment removed
            'Path without leading slash' => ['admin/dashboard', ['admin', 'dashboard']],
            'Path with multiple slashes' => ['//api//v1//resource', ['api', 'v1', 'resource']], // All empty segments removed
            'Empty path string' => ['', ['']], // Empty path special case
            'Only slashes' => ['///', ['']], // Multiple slashes special case -> root
        ];
    }

    // ... testGetRequestUri method ...

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function requestUriProvider(): array
    {
        // Adjusted expectations based on preg_split('#/#', ..., PREG_SPLIT_NO_EMPTY) logic
        return [
            'Root URL' => ['/', ['']],
            'Root URL with query' => ['/?param=value', ['']],
            'Simple path' => ['/users/list', ['users', 'list']],
            'Path with query string' => ['/products/view?id=123&sort=asc', ['products', 'view']],
            'Path with trailing slash' => ['/articles/', ['articles']], // Trailing empty segment removed
            'Path with trailing slash and query' => ['/articles/?page=2', ['articles']], // Trailing empty segment removed
            'Path without leading slash' => ['admin/dashboard?token=xyz', ['admin', 'dashboard']],
            'Path with multiple slashes and query' => ['//api//v1//resource?filter=active', ['api', 'v1', 'resource']], // All empty segments removed
            'Empty URI string' => ['', ['']],
            'Query string only' => ['?param=only', ['']], // Only query string -> root path
            'Multiple slashes only' => ['///?a=b', ['']], // Multiple slashes special case -> root
        ];
    }
}
