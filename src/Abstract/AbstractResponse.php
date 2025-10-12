<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Cli;
use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Core\View;
use Waffle\Exception\RenderingException; // <-- NOUVEL IMPORT : Exception pour les erreurs de rendu (Bad Request 400)
use Waffle\Interface\CliInterface;
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

    /**
     * @var View|null
     */
    private(set) null|View $view {
        set => $this->view = $value;
    }

    private(set) bool $cli {
        set => $this->cli = $value;
    }

    private(set) Cli|Request $handler {
        set => $this->handler = $value;
    }

    #[\Override]
    public function build(CliInterface|RequestInterface $handler): void
    {
        $this->view = null;
        /** @var Cli|Request $handler */
        $this->cli = $handler->cli;
        $this->handler = $this->cli && $handler instanceof Cli ? new Request(cli: true) : $handler;
    }

    /**
     * @throws RenderingException
     */
    #[\Override]
    public function render(): void
    {
        $view = $this->callControllerAction();
        $this->view = $view;

        if (null !== $view) {
            /** @var string $env */
            $env = $this->handler->env[Constant::APP_ENV];
            $this->rendering(
                view: $view,
                env: $env,
            );
        }
    }

    /**
     * @throws RenderingException
     */
    private function callControllerAction(): null|View
    {
        $class = null;
        $method = null;
        $path = null;
        $name = null;
        $cli = false;
        $error = false;
        $argTypes = [];
        $args = [];
        $controller = $this->controllerValues(route: $this->handler->currentRoute);
        if (null !== $controller) {
            /**
             * @var string|int $key
             * @var string|array<non-empty-string, string> $value
             */
            foreach ($controller as $key => $value) {
                match ($key) {
                    Constant::CLASSNAME => $class = $value,
                    Constant::METHOD => $method = $value,
                    Constant::ARGUMENTS => $argTypes = $value,
                    Constant::PATH => $path = $value,
                    Constant::NAME => $name = $value,
                    0 => $cli = true,
                    default => $error = true,
                };
            }
            if ((!$cli || !$error) && (null !== $path && null !== $name)) {
                $class = new $class();
                /** @var array<non-empty-string, string> $argTypes */
                foreach ($argTypes as $keyType => $argType) {
                    $arg = new $argType();
                    if (!class_exists(class: $argType)) {
                        $arg = $this->getRouteArgument(
                            name: $keyType,
                            type: $argType,
                        );
                    }
                    $args[] = $arg;
                }
                /** @var callable $callable */
                $callable = [$class, $method];
                /** @var View $view */
                $view = call_user_func_array(
                    callback: $callable,
                    args: $args,
                );
                return $view;
            }
        }

        return null;
    }

    /**
     * @throws RenderingException
     */
    private function getRouteArgument(string $name, string $type = Constant::TYPE_STRING): string|int|null
    {
        $arg = null;
        if (null !== $this->handler->currentRoute) {
            $arguments = $this->handler->currentRoute[Constant::ARGUMENTS] ?? [];
            $path = $this->getPathUri(path: $this->handler->currentRoute[Constant::PATH] ?? Constant::EMPTY_STRING);
            $url = $this->getRequestUri(uri: $this->handler->server[Constant::REQUEST_URI]);
            foreach ($arguments as $key => $_) {
                if ($name === $key) {
                    for ($i = 0, $iMax = count(value: $path); $i < $iMax; $i++) {
                        preg_match(
                            pattern: '/^\{(.*)}$/',
                            subject: $path[$i],
                            matches: $matches,
                            flags: PREG_UNMATCHED_AS_NULL,
                        );
                        $m0 = isset($matches[0]) && '' !== $matches[0];
                        $m1 = isset($matches[1]) && '' !== $matches[1];
                        if ($m0 && $m1 && $name === $matches[1]) {
                            // TODO(@supa-chayajin): Implements this in Security
                            $arg = match ($type) {
                                Constant::TYPE_INT => is_numeric(value: $url[$i])
                                    ? (int) $url[$i]
                                    : throw new RenderingException(
                                        message: sprintf(
                                            'URL parameter "%s" expects type int, got invalid value: "%s".',
                                            $name,
                                            $url[$i],
                                        ),
                                        code: 400,
                                    ),
                                default => $url[$i],
                            };
                        }
                    }
                }
            }
        }

        return $arg;
    }
}
