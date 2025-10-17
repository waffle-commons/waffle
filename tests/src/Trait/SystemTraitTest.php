<?php

declare(strict_types=1);

namespace WaffleTests\Trait;

use PHPUnit\Framework\Attributes\CoversTrait;
use Waffle\Trait\SystemTrait;
use WaffleTests\AbstractTestCase as TestCase;

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
        $isUsed = class_uses($testObject);
        if ($isUsed) {
            static::assertContains(SystemTrait::class, $isUsed, 'The test object should use the SystemTrait.');
        }
    }
}
