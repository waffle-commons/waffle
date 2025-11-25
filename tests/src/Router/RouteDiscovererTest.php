<?php

declare(strict_types=1);

namespace WaffleTests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Router\RouteDiscoverer;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(RouteDiscoverer::class)]
final class RouteDiscovererTest extends TestCase
{
    private null|string $tempDir = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/waffle_discoverer_' . uniqid();
        mkdir($this->tempDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testDiscoverReturnsEmptyArrayIfDirectoryIsFalse(): void
    {
        $discoverer = new RouteDiscoverer(directory: false);

        // Mock container not needed since discover checks files first
        $container = $this->createMock(\Waffle\Interface\ContainerInterface::class);

        static::assertEmpty($discoverer->discover($container));
    }

    public function testDiscoverReturnsEmptyArrayIfDirectoryIsEmpty(): void
    {
        $discoverer = new RouteDiscoverer(directory: $this->tempDir);
        $container = $this->createMock(\Waffle\Interface\ContainerInterface::class);

        static::assertEmpty($discoverer->discover($container));
    }
}
