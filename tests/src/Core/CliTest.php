<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Abstract\AbstractCli;
use Waffle\Core\Cli;

#[CoversClass(Cli::class)]
final class CliTest extends TestCase
{
    /**
     * This test ensures that the Cli class can be successfully instantiated
     * and that it correctly extends its abstract parent.
     */
    public function testCanBeInstantiated(): void
    {
        // When: A new Cli object is created.
        $cli = new Cli();

        // Then: It should be an instance of both Cli and AbstractCli.
        $this->assertInstanceOf(Cli::class, $cli);
        $this->assertInstanceOf(AbstractCli::class, $cli);
    }
}
