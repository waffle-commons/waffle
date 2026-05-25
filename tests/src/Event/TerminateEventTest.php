<?php

declare(strict_types=1);

namespace WaffleTests\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waffle\Event\TerminateEvent;
use WaffleTests\AbstractTestCase as TestCase;

final class TerminateEventTest extends TestCase
{
    public function testExposesRequestAndResponse(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $event = new TerminateEvent($request, $response);

        static::assertSame($request, $event->request);
        static::assertSame($response, $event->response);
    }
}
