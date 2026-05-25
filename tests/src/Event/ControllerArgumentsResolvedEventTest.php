<?php

declare(strict_types=1);

namespace WaffleTests\Event;

use Psr\Http\Message\ServerRequestInterface;
use Waffle\Event\ControllerArgumentsResolvedEvent;
use WaffleTests\AbstractTestCase as TestCase;

final class ControllerArgumentsResolvedEventTest extends TestCase
{
    public function testExposesAllFields(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $arguments = [0 => 42, 1 => 'hello'];

        $event = new ControllerArgumentsResolvedEvent(
            request: $request,
            controller: 'App\\Controller\\Foo',
            method: 'bar',
            arguments: $arguments,
        );

        static::assertSame($request, $event->request);
        static::assertSame('App\\Controller\\Foo', $event->controller);
        static::assertSame('bar', $event->method);
        static::assertSame($arguments, $event->arguments);
    }
}
