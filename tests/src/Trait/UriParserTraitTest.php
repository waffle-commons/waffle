<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Waffle\Trait\UriParserTrait;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Trait\Helper\TraitUri;

#[CoversTrait(UriParserTrait::class)]
final class UriParserTraitTest extends TestCase
{
    // Inject the trait into an anonymous class for testing
    private object $traitObject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new TraitUri();
    }

    /**
     * @return array<string, array{string, string[]}>
     */
    public static function pathUriProvider(): array
    {
        return [
            'Root path' => ['/', ['']],
            'Simple path' => ['/users/list', ['users', 'list']],
            'Path with trailing slash' => ['/articles/', ['articles']], // Corrected expectation
            'Path without leading slash' => ['admin/dashboard', ['admin', 'dashboard']],
            'Path with multiple slashes' => ['//api//v1//resource', ['api', 'v1', 'resource']], // Corrected expectation
            'Empty path string' => ['', ['']],
            'Only slashes' => ['///', ['']],
        ];
    }

    /**
     * @param string $path
     * @param string[] $expectedSegments
     */
    #[DataProvider('pathUriProvider')]
    public function testGetPathUri(string $path, array $expectedSegments): void
    {
        static::assertSame($expectedSegments, $this->traitObject->callGetPathUri($path));
    }

    // --- Added Tests for getRequestUri ---

    /**
     * @return array<string, array{string, string[]}>
     */
    public static function requestUriProvider(): array
    {
        // Similar cases as getPathUri, but also testing query string removal
        return [
            'Root URL' => ['/', ['']],
            'Simple path' => ['/users/list', ['users', 'list']],
            'Path with query string' => ['/products/view?id=123', ['products', 'view']],
            'Path with trailing slash' => ['/articles/', ['articles']],
            'Path with trailing slash and query' => ['/articles/?page=2', ['articles']],
            'Path with multiple slashes' => ['//api//v1//resource', ['api', 'v1', 'resource']],
            'Path with multiple slashes and query' => ['//api//v1//resource?filter=active', ['api', 'v1', 'resource']],
            'Empty URI string' => ['', ['']],
            'Only slashes' => ['///', ['']],
            'Only slashes with query' => ['///?a=b', ['']],
            'Query string only' => ['?param=only', ['']], // Corrected: Path is root
            'Path ending with ?' => ['/path?', ['path']],
        ];
    }

    /**
     * @param string $uri
     * @param string[] $expectedSegments
     */
    #[DataProvider('requestUriProvider')]
    public function testGetRequestUri(string $uri, array $expectedSegments): void
    {
        static::assertSame($expectedSegments, $this->traitObject->callGetRequestUri($uri));
    }
}
