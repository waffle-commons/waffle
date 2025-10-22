<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use Waffle\Core\View;
use WaffleTests\AbstractTestCase as TestCase;

final class ViewTest extends TestCase
{
    public function testConstructorWithData(): void
    {
        // --- Test Condition ---
        $data = ['key' => 'value', 'status' => 200];

        // --- Execution ---
        $view = new View(data: $data);

        // --- Assertions ---
        // Assert that the public property 'data' holds the exact array we passed.
        static::assertSame($data, $view->data);
    }

    public function testConstructorWithNullData(): void
    {
        // --- Execution ---
        // Instantiate the View without providing any data.
        $view = new View();

        // --- Assertions ---
        // Assert that the public property 'data' is null by default.
        static::assertNull($view->data);
    }
}
