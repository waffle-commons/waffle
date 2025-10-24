<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use ReflectionMethod;
use ReflectionNamedType;
use Waffle\Core\Constant;
use Waffle\Core\View;
use Waffle\Enum\HttpBag;
use Waffle\Exception\RenderingException;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\RequestInterface;
use Waffle\Interface\ResponseInterface;
use Waffle\Trait\ReflectionTrait;
use Waffle\Trait\RenderingTrait;
use Waffle\Trait\RequestTrait;

abstract class AbstractResponse implements ResponseInterface
{
    use ReflectionTrait;
    use RenderingTrait;
    use RequestTrait;

    private null|View $view = null;
    private RequestInterface $handler;
    public null|ContainerInterface $container = null;

    #[\Override]
    public function build(RequestInterface $handler): void
    {
        $this->view = null;
        $this->handler = $handler;
        $this->container = $handler->container;
    }

    /**
     * @throws RenderingException
     */
    #[\Override]
    public function render(): void
    {
        $this->view = $this->callControllerAction();

        if (null !== $this->view) {
            $environment = $this->handler->bag(key: HttpBag::ENV);
            /** @var string $env */
            $env = $environment->get(
                key: Constant::APP_ENV,
                default: Constant::ENV_PROD,
            );
            $this->rendering(
                view: $this->view,
                env: $env,
            );
        }
    }

    /**
     * @throws RenderingException
     */
    private function callControllerAction(): null|View
    {
        $route = $this->handler->currentRoute;

        if (null === $route) {
            return null;
        }

        $className = $route[Constant::CLASSNAME];
        $methodName = $route[Constant::METHOD];

        if (!$this->container?->has(id: $className)) {
            $this->container?->set(
                id: $className,
                concrete: $className,
            );
        }

        /** @var object $controller */
        $controller = $this->container?->get(id: $className);
        if (!method_exists($controller, $methodName)) {
            return null;
        }

        $reflectionMethod = new ReflectionMethod($controller, $methodName);
        $args = $this->resolveControllerArguments(
            method: $reflectionMethod,
            route: $route,
        );

        /** @var View $view */
        $view = $reflectionMethod->invokeArgs($controller, $args);

        return $view;
    }

    /**
     * @param array{
     *       classname: class-string,
     *       method: string,
     *       arguments: array<string, mixed>,
     *       path: string,
     *       name: non-falsy-string
     *  } $route
     * @return array<int, mixed>
     * @throws RenderingException
     */
    private function resolveControllerArguments(ReflectionMethod $method, array $route): array
    {
        $args = [];
        $parameters = $method->getParameters();

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            /** @var ReflectionNamedType $paramType */
            $paramType = $parameter->getType();
            /** @var string $type */
            $type = $paramType?->getName() ?? 'string';

            $argValue = $this->getRouteArgument(
                name: $name,
                type: $type,
                route: $route,
            );
            if (null !== $argValue) {
                $args[] = $argValue;
                continue;
            }

            if ($this->container?->has($type)) {
                $args[] = $this->container->get($type);
            }
        }

        return $args;
    }

    /**
     * @param string $name
     * @param string $type
     * @param array{
     *        classname: class-string,
     *        method: string,
     *        arguments: array<string, mixed>,
     *        path: string,
     *        name: non-falsy-string
     *  } $route
     * @return string|int|null
     * @throws RenderingException
     */
    private function getRouteArgument(string $name, string $type, array $route): string|int|null
    {
        $rawValue = $this->extractArgumentFromUri(
            name: $name,
            route: $route,
        );

        if (null === $rawValue) {
            return null;
        }

        return $this->castArgument(
            name: $name,
            value: $rawValue,
            type: $type,
        );
    }

    /**
     * @param string $name
     * @param array{
     *         classname: class-string,
     *         method: string,
     *         arguments: array<string, mixed>,
     *         path: string,
     *         name: non-falsy-string
     *  } $route
     * @return string|null
     */
    private function extractArgumentFromUri(string $name, array $route): null|string
    {
        $pathSegments = $this->getPathUri(path: $route[Constant::PATH]);
        $server = $this->handler->bag(key: HttpBag::SERVER);
        $serverUri = $server->get(
            key: Constant::REQUEST_URI,
            default: Constant::EMPTY_STRING,
        );
        $urlSegments = $this->getRequestUri(uri: $serverUri);

        foreach ($pathSegments as $i => $pathSegment) {
            $matches = [];
            if (preg_match('/^\{(.+)}$/', $pathSegment, $matches) && $matches[1] === $name) {
                return $urlSegments[$i] ?? null;
            }
        }

        return null;
    }

    /**
     * @throws RenderingException
     */
    private function castArgument(string $name, string $value, string $type): string|int
    {
        return match ($type) {
            Constant::TYPE_INT => is_numeric($value)
                ? (int) $value
                : throw new RenderingException(
                    message: sprintf('URL parameter "%s" expects type int, got invalid value: "%s".', $name, $value),
                    code: 400,
                ),
            default => $value,
        };
    }
}
