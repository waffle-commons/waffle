<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Response;
use Waffle\Interface\CliInterface;
use Waffle\Interface\ResponseInterface;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
abstract class AbstractCli implements CliInterface
{
    /**
     * @var array<mixed>
     */
    public array $globals
        {
            get => $GLOBALS;
        }

    /**
     * @var array<mixed>
     */
    public array $server
        {
            get => $_SERVER;
        }

    /**
     * @var array<mixed>
     */
    public array $get
        {
            get => $_GET;
        }

    /**
     * @var array<mixed>
     */
    public array $post
        {
            get => $_POST;
        }

    /**
     * @var array<mixed>
     */
    public array $files
        {
            get => $_FILES;
        }

    /**
     * @var array<mixed>
     */
    public array $cookie
        {
            get => $_COOKIE;
        }

    /**
     * @var array<mixed>
     */
    public array $session
        {
            get => $_SESSION;
        }

    /**
     * @var array<mixed>
     */
    public array $request
        {
            get => $_REQUEST;
        }

    /**
     * @var array<mixed>
     */
    public array $env
        {
            get => $_ENV;
        }

    protected(set) bool $cli = true
        {
            set => $this->cli = $value;
        }

    /**
     * @var array{
     *     classname: string,
     *     method: non-empty-string,
     *     arguments: array<non-empty-string, string>,
     *     path: string,
     *     name: non-falsy-string
     * }|null
     */
    protected(set) ?array $currentRoute = null
        {
            get => $this->currentRoute;
            set => $this->currentRoute = $value;
        }

    #[\Override]
    public function configure(bool $cli): void
    {
        $this->cli = $cli;
    }

    #[\Override]
    public function process(): ResponseInterface
    {
        return new Response(handler: $this);
    }

    /**
     * @param array{
     *      classname: string,
     *      method: non-empty-string,
     *      arguments: array<non-empty-string, string>,
     *      path: string,
     *      name: non-falsy-string
     *  }|null $route
     * @return $this
     */
    #[\Override]
    public function setCurrentRoute(?array $route = null): self
    {
        $this->currentRoute = $route;

        return $this;
    }
}
