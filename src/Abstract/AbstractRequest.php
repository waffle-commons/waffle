<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Response;
use Waffle\Core\View;
use Waffle\Exception\RenderingException;
use Waffle\Interface\RequestInterface;
use Waffle\Interface\ResponseInterface;
use Waffle\Trait\RequestTrait;

abstract class AbstractRequest implements RequestInterface
{
    use RequestTrait;

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

    private(set) bool $cli = false
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
    private(set) ?array $currentRoute = null
        {
            get => $this->currentRoute;
            set => $this->currentRoute = $value;
        }

    abstract public function __construct(bool $cli = false);

    public function configure(bool $cli): void
    {
        $this->cli = $cli;
    }

    public function process(): ResponseInterface
    {
        if ($this->currentRoute === null && $this->cli === false) {
            new RenderingException()->throw(view: new View(data: [
                'message' => 'unknown route',
                'code' => 404,
            ]));
            http_response_code(response_code: 404);
            // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
            exit;
        }

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
    public function setCurrentRoute(?array $route = null): self
    {
        $this->currentRoute = $route;

        return $this;
    }
}
