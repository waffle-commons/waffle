<?php

declare(strict_types=1);

namespace WaffleTests\Core;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Contracts\Controller\BaseControllerInterface;
use Waffle\Core\BaseController;
use WaffleTests\AbstractTestCase as TestCase;

#[CoversClass(BaseController::class)]
final class BaseControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new BaseController();

        static::assertInstanceOf(BaseController::class, $controller, 'BaseController should be instantiable.');
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $controller = new BaseController();

        static::assertInstanceOf(
            BaseControllerInterface::class,
            $controller,
            'BaseController must implement BaseControllerInterface.',
        );
    }
}
