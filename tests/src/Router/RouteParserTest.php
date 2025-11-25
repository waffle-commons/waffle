<?php

declare(strict_types=1);

namespace WaffleTests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Router\RouteParser;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Trait\Helper\DuplicateRouteController;

#[CoversClass(RouteParser::class)]
final class RouteParserTest extends TestCase
{
    public function testParseReturnsEmptyIfClassDoesNotExist(): void
    {
        $parser = new RouteParser();
        $container = $this->createRealContainer();

        $routes = $parser->parse($container, 'NonExistent\Class');

        static::assertEmpty($routes);
    }

    public function testParseReturnsEmptyIfClassIsAbstract(): void
    {
        $parser = new RouteParser();
        $container = $this->createRealContainer();

        $routes = $parser->parse($container, \WaffleTests\Core\Helper\AbstractUninstantiable::class);

        static::assertEmpty($routes);
    }

    public function testParseIgnoresDuplicateRoutes(): void
    {
        $container = $this->createRealContainer();
        $container->set(DuplicateRouteController::class, DuplicateRouteController::class);

        $parser = new RouteParser();
        $routes = $parser->parse($container, DuplicateRouteController::class);

        // Assert
        // DuplicateRouteController has 2 methods pointing to '/duplicate',
        // so only the first one should be registered.
        static::assertCount(1, $routes, 'Duplicate routes should be filtered out.');
        static::assertSame('default_first_method', $routes[0]['name']);
    }
}
