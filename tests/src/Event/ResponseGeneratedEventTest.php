<?php

declare(strict_types=1);

namespace WaffleTests\Event;

use Psr\Http\Message\ResponseInterface;
use Waffle\Event\ResponseGeneratedEvent;
use WaffleTests\AbstractTestCase as TestCase;

final class ResponseGeneratedEventTest extends TestCase
{
    public function testGetResponseReturnsConstructorResponse(): void
    {
        $response = $this->createStub(ResponseInterface::class);

        $event = new ResponseGeneratedEvent($response);

        static::assertSame($response, $event->response);
    }

    public function testWithResponseProducesNewEventWithSwappedResponse(): void
    {
        $a = $this->createStub(ResponseInterface::class);
        $b = $this->createStub(ResponseInterface::class);

        $event = new ResponseGeneratedEvent($a);
        $next = $event->withResponse($b);

        static::assertNotSame($event, $next);
        static::assertSame($a, $event->response);
        static::assertSame($b, $next->response);
    }
}
