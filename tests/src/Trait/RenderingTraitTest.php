<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\TestCase;
use Waffle\Core\Constant;
use Waffle\Core\View;
use Waffle\Trait\RenderingTrait;

final class RenderingTraitTest extends TestCase
{
    /**
     * An anonymous class that uses the trait to be tested.
     */
    private object $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new class {
            use RenderingTrait;
        };
    }

    public function testRenderingOutputsJsonInDevEnvironment(): void
    {
        // --- Test Condition ---
        // We create a simple View object.
        $view = new View(data: ['status' => 'ok']);

        // --- Execution ---
        // We start the output buffer to capture what the trait "echoes".
        ob_start();
        // We call the rendering method, simulating a 'dev' environment.
        $this->traitObject->rendering($view, Constant::ENV_DEV);
        $output = ob_get_clean();

        // --- Assertions ---
        // We assert that the output is a valid JSON string.
        $this->assertJson($output);
        $this->assertStringContainsString('"status": "ok"', $output);
    }

    public function testRenderingOutputsNothingInTestEnvironment(): void
    {
        // --- Test Condition ---
        $view = new View(data: ['status' => 'ok']);

        // --- Execution ---
        ob_start();
        // We call the rendering method, simulating the 'test' environment.
        $this->traitObject->rendering($view, Constant::ENV_TEST);
        $output = ob_get_clean();

        // --- Assertions ---
        // We assert that nothing was echoed, as expected in a test environment.
        $this->assertEmpty($output);
    }

    public function testThrowMethodRendersContent(): void
    {
        // --- Test Condition ---
        $view = new View(data: ['error' => 'critical']);

        // --- Execution ---
        ob_start();
        // The throw() method should always render output, regardless of the environment.
        $this->traitObject->throw($view);
        $output = ob_get_clean();

        // --- Assertions ---
        $this->assertJson($output);
        $this->assertStringContainsString('"error": "critical"', $output);
    }
}
