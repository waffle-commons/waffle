<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Constant;
use Waffle\Core\Cli;
use Waffle\Core\Request;
use Waffle\Core\View;
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
    private(set) ? View $view
        {
            set => $this->view = $value;
        }

    private(set) bool $cli
        {
            set => $this->cli = $value;
        }

    private(set) Cli | Request $handler
        {
            set => $this->handler = $value;
        }

    abstract public function __construct(CliInterface|RequestInterface $handler);

    public function build(CliInterface|RequestInterface $handler): void
    {
        $this->view = null;
        /** @var Cli|Request $handler */
        $this->cli = $handler->cli;
        $this->handler = ($this->cli && $handler instanceof Cli) ? new Request(cli: true) : $handler;
    }

    public function render(): void
    {
        $class = $method = $cli = $error = null;
        $argTypes = $args = [];
        $controller = $this->controllerValues(route: $this->handler->currentRoute);
        /**
         * @var string|int $key
         * @var string|array<non-empty-string, string> $value
         */
        foreach ($controller as $key => $value) {
            match ($key) {
                Constant::CLASSNAME => $class = $value,
                Constant::METHOD => $method = $value,
                Constant::ARGUMENTS => $argTypes = $value,
                0 => $cli = true,
                default => $error = true,
            };
        }
        if ($cli !== true || $error === true) {
            $class = new $class();
            /** @var array<non-empty-string, string> $argTypes */
            foreach ($argTypes as $keyType => $argType) {
                if (class_exists(class: $argType)) {
                    $arg = new $argType();
                } else {
                    $arg = $this->getRouteArgument(name: $keyType, type: $argType);
                }
                $args[] = $arg;
            }
            /** @var callable $callable */
            $callable = [$class, $method];
            /** @var View $view */
            $view = call_user_func_array(callback: $callable, args: $args);
            $this->view = $view;
            /** @var string $env */
            $env = $this->handler->env[Constant::APP_ENV];
            $this->rendering(view: $view, env: $env);
        }
    }

    private function getRouteArgument(string $name, string $type = Constant::TYPE_STRING): string|int|null
    {
        $arg = null;
        if ($this->handler->currentRoute !== null) {
            $arguments = $this->handler->currentRoute[Constant::ARGUMENTS] ?: [];
            $path = $this->getPathUri(path: $this->handler->currentRoute[Constant::PATH] ?: Constant::EMPTY_STRING);
            $url = $this->getRequestUri(uri: $this->handler->server[Constant::REQUEST_URI]);
            foreach ($arguments as $key => $value) {
                if ($name === $key) {
                    for ($i = 0; $i < count(value: $path); $i++) {
                        preg_match(
                            pattern: '/^\{(.*)}$/',
                            subject: $path[$i],
                            matches: $matches,
                            flags: PREG_UNMATCHED_AS_NULL
                        );
                        if (!empty($matches[0])) {
                            if (!empty($matches[1]) && $name === $matches[1]) {
                                $arg = match ($type) {
                                    Constant::TYPE_INT => intval($url[$i]),
                                    default => $url[$i],
                                };
                            }
                        }
                    }
                }
            }
        }

        return $arg;
    }
}
