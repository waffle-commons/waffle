<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Waffle\Abstract\AbstractController;

#[AllowMockObjectsWithoutExpectations]
class AbstractControllerTest extends TestCase
{
    public function testJsonResponseCreatesValidResponse(): void
    {
        $controller = new class extends AbstractController {
            public function testJson(array $data)
            {
                return $this->jsonResponse($data);
            }
        };

        $factory = $this->createMock(ResponseFactoryInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $response->method('getBody')->willReturn($stream);
        $response
            ->expects($this->once())
            ->method('withHeader')
            ->with('Content-Type', 'application/json')
            ->willReturnSelf();

        $factory->expects($this->once())->method('createResponse')->with(200)->willReturn($response);

        $controller->setResponseFactory($factory);
        $controller->testJson(['key' => 'value']);
    }
}
