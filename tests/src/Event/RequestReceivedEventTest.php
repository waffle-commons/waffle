<?php

declare(strict_types=1);

namespace WaffleTests\Event;

use Psr\Http\Message\ServerRequestInterface;
use Waffle\Event\RequestReceivedEvent;
use WaffleTests\AbstractTestCase as TestCase;

final class RequestReceivedEventTest extends TestCase
{
    public function testGetRequestReturnsConstructorRequest(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);

        $event = new RequestReceivedEvent($request);

        static::assertSame($request, $event->getRequest());
    }

    public function testWithRequestProducesNewEventWithSwappedRequest(): void
    {
        $a = $this->createStub(ServerRequestInterface::class);
        $b = $this->createStub(ServerRequestInterface::class);

        $event = new RequestReceivedEvent($a);
        $next = $event->withRequest($b);

        static::assertNotSame($event, $next);
        static::assertSame($a, $event->getRequest());
        static::assertSame($b, $next->getRequest());
    }
}
