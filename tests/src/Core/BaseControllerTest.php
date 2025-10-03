<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Core\BaseController;
use Waffle\Interface\BaseControllerInterface;

#[CoversClass(BaseController::class)]
final class BaseControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new BaseController();

        $this->assertInstanceOf(
            expected: BaseController::class,
            actual: $controller,
            message: 'BaseController should be instantiable.'
        );
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $controller = new BaseController();

        $this->assertInstanceOf(
            expected: BaseControllerInterface::class,
            actual: $controller,
            message: 'BaseController must implement BaseControllerInterface.'
        );
    }
}
