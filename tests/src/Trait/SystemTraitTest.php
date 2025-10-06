<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Waffle\Trait\SystemTrait;

#[CoversTrait(SystemTrait::class)]
final class SystemTraitTest extends TestCase
{
    public function testTraitExists(): void
    {
        // We create an anonymous class that uses the trait
        $testObject = new class {
            use SystemTrait;
        };

        // We assert that the object uses the trait
        static::assertContains(
            SystemTrait::class,
            class_uses($testObject),
            'The test object should use the SystemTrait.',
        );
    }
}
