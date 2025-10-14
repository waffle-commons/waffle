<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Abstract\AbstractCli;
use Waffle\Attribute\Configuration;
use Waffle\Core\Cli;
use Waffle\Core\Container;
use Waffle\Core\Security;

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
        $config = new Configuration();
        $security = new Security(cfg: $config);
        $cli = new Cli(container: new Container(security: $security));

        // Then: It should be an instance of both Cli and AbstractCli.
        static::assertInstanceOf(Cli::class, $cli);
        static::assertInstanceOf(AbstractCli::class, $cli);
    }
}
