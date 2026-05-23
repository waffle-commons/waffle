<?php

declare(strict_types=1);

namespace WaffleTests\Handler;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\EventDispatcher\EventDispatcherInterface;
use Waffle\Commons\Contracts\Handler\ArgumentResolverInterface;
use Waffle\Commons\Contracts\Handler\ResponseConverterInterface;
use Waffle\Event\ControllerArgumentsResolvedEvent;
use Waffle\Handler\ControllerDispatcher;
use WaffleTests\Abstract\Helper\StubServerRequest;

#[CoversClass(ControllerDispatcher::class)]
#[AllowMockObjectsWithoutExpectations]
final class ControllerDispatcherEventTest extends TestCase
{
    public function testDispatcherEmitsArgumentsResolvedEventAndAppliesMutatedArgs(): void
    {
        $controller = new class {
            public ?array $capturedArgs = null;

            public function action(int $id, string $name): ?array
            {
                $this->capturedArgs = [$id, $name];
                return null;
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($controller);

        // Initial args from resolver
        $resolver = $this->createMock(ArgumentResolverInterface::class);
        $resolver->method('resolve')->willReturn([1, 'original']);

        // Event dispatcher rewrites the args to [99, 'mutated'].
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof ControllerArgumentsResolvedEvent) {
                    return new ControllerArgumentsResolvedEvent(
                        request: $event->request,
                        controller: $event->controller,
                        method: $event->method,
                        arguments: [99, 'mutated'],
                    );
                }
                return $event;
            });

        $responseConverter = $this->createMock(ResponseConverterInterface::class);
        $expectedResponse = $this->createStub(ResponseInterface::class);
        $responseConverter->expects($this->once())->method('convert')->with(null)->willReturn($expectedResponse);

        $dispatcher = new ControllerDispatcher(
            container: $container,
            dispatcher: $eventDispatcher,
            argumentResolver: $resolver,
            responseConverter: $responseConverter,
        );

        $request = new StubServerRequest('GET', '/');
        $request = $request->withAttribute('_classname', 'TestController')->withAttribute('_method', 'action');

        $result = $dispatcher->handle($request);

        // Controller saw the dispatched event's mutated args, not the resolver's originals.
        static::assertSame([99, 'mutated'], $controller->capturedArgs);
        // The injected ResponseConverter handled the null return value.
        static::assertSame($expectedResponse, $result);
    }

    public function testDispatcherToleratesEventDispatcherReturningUnknownEventShape(): void
    {
        // PSR-14 says dispatch() returns the event (or a derivative). If a listener
        // returns something else, the dispatcher must NOT crash — args are simply not mutated.
        $controller = new class {
            public ?array $capturedArgs = null;

            public function action(int $id): ?array
            {
                $this->capturedArgs = [$id];
                return null;
            }
        };

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($controller);

        $resolver = $this->createMock(ArgumentResolverInterface::class);
        $resolver->method('resolve')->willReturn([7]);

        // Misbehaving dispatcher returns an unrelated object.
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturn(new \stdClass());

        $responseConverter = $this->createMock(ResponseConverterInterface::class);
        $responseConverter->method('convert')->willReturn($this->createStub(ResponseInterface::class));

        $dispatcher = new ControllerDispatcher(
            container: $container,
            dispatcher: $eventDispatcher,
            argumentResolver: $resolver,
            responseConverter: $responseConverter,
        );

        $request = new StubServerRequest('GET', '/');
        $request = $request->withAttribute('_classname', 'TestController')->withAttribute('_method', 'action');

        $dispatcher->handle($request);

        // Original resolver args are preserved.
        static::assertSame([7], $controller->capturedArgs);
    }
}
