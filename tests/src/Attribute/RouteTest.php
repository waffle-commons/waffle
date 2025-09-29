<?php

declare(strict_types=1);

namespace WaffleTests\Attribute;

use PHPUnit\Framework\TestCase;
use Waffle\Attribute\Argument;
use Waffle\Attribute\Route;

final class RouteTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        // --- Test Condition ---
        $path = '/test-path';
        $name = 'test_name';
        $arguments = [new Argument(classType: 'string', paramName: 'id')];

        // --- Execution ---
        $route = new Route($path, $name, $arguments);

        // --- Assertions ---
        $this->assertSame($path, $route->path);
        $this->assertSame($name, $route->name);
        $this->assertSame($arguments, $route->arguments);
    }

    public function testConstructorWithDefaultParameters(): void
    {
        // --- Test Condition ---
        $path = '/another-path';

        // --- Execution ---
        $route = new Route($path);

        // --- Assertions ---
        $this->assertSame($path, $route->path);
        // Assert that the optional parameters are null by default.
        $this->assertNull($route->name);
        $this->assertNull($route->arguments);
    }
}
