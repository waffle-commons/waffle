<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use Waffle\Core\Cli;
use Waffle\Core\Response;
use PHPUnit\Framework\TestCase;

class CliTest extends TestCase
{
    public function testProcess(): void
    {
        // Given
        $class = $this->getClass();

        // When
        $result = $class->process();

        // Expects
        $this->assertInstanceOf(Response::class, $result);
    }

    private function getClass(): Cli
    {
        return new Cli();
    }
}
