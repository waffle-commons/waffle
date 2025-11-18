<?php

declare(strict_types=1);

namespace Waffle\Interface;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The Kernel is the heart of the application.
 * It is responsible for booting the system and handling HTTP requests.
 */
interface KernelInterface
{
    /**
     * Boots the kernel and initializes environment/configuration.
     */
    public function boot(): self;

    /**
     * Configures the system (container, security, routes).
     */
    public function configure(): self;

    /**
     * Handles a Request and converts it into a Response.
     *
     * @param ServerRequestInterface $request The incoming HTTP request.
     * @return ResponseInterface The generated HTTP response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;
}