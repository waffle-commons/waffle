<?php

declare(strict_types=1);

namespace WaffleTests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Attribute\Route;
use Waffle\Router\RouteParser;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Trait\Helper\DuplicateRouteController;

#[CoversClass(RouteParser::class)]
final class RouteParserTest extends TestCase
{
    public function testParseIgnoresDuplicateRoutes(): void
    {
        // Arrange
        // We define a controller class on the fly using eval or anonymous class if possible,
        // but attributes on anon classes are tricky.
        // Instead, we'll mock the container resolution to return an object with duplicate attributes/routes.

        // Better approach: Create a real dummy controller file with duplicates in the test helper directory.
        // For this example, let's assume we have a helper class 'DuplicateRouteController'.

        $container = $this->createRealContainer();
        $container->set(DuplicateRouteController::class, DuplicateRouteController::class);

        $parser = new RouteParser();

        // Act
        $routes = $parser->parse($container, DuplicateRouteController::class);

        // Assert
        // DuplicateRouteController has 2 methods pointing to '/duplicate',
        // so only the first one should be registered.
        static::assertCount(1, $routes, 'Duplicate routes should be filtered out.');
        static::assertSame('default_first_method', $routes[0]['name']);
    }
}
