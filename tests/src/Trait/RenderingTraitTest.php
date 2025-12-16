<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Core\View;
use Waffle\Exception\RenderingException;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Trait\Helper\TraitObject;

final class RenderingTraitTest extends TestCase
{
    /**
     * An anonymous class that uses the trait to be tested.
     */
    private TraitObject $traitObject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new TraitObject();
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
        $output = ob_get_clean() ?? '';

        // --- Assertions ---
        // We assert that the output is a valid JSON string.
        static::assertJson($output);
        static::assertStringContainsString('"status": "ok"', $output);
    }

    public function testRenderingOutputsNothingInTestEnvironment(): void
    {
        // --- Test Condition ---
        $view = new View(data: ['status' => 'ok']);

        // --- Execution ---
        ob_start();
        // We call the rendering method, simulating the 'test' environment.
        $this->traitObject->rendering($view, Constant::ENV_TEST);
        $output = ob_get_clean() ?? '';

        // --- Assertions ---
        // We assert that nothing was echoed, as expected in a test environment.
        static::assertEmpty($output);
    }

    public function testThrowMethodRendersContent(): void
    {
        // --- Test Condition ---
        $view = new View(data: ['error' => 'critical']);

        // --- Execution ---
        ob_start();
        // The throw() method should always render output, regardless of the environment.
        $this->traitObject->throw($view);
        $output = ob_get_clean() ?? '';

        // --- Assertions ---
        static::assertJson($output);
        static::assertStringContainsString('"error": "critical"', $output);
    }

    public function testRenderingThrowsRenderingExceptionOnJsonError(): void
    {
        // We expect our custom exception to be thrown.
        static::expectException(RenderingException::class);

        // Create a View with data that cannot be JSON encoded (a stream resource).
        $problematicData = ['data' => fopen('php://memory', 'r')];
        $view = new View(data: $problematicData);

        // Execute the rendering method, which should trigger the error.
        $this->traitObject->rendering($view, Constant::ENV_TEST);
    }
}
