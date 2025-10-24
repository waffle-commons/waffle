<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractRequest;
use Waffle\Interface\ContainerInterface;

class Request extends AbstractRequest
{
    /**
     * @template T
     * @param ContainerInterface $container
     * @param array{
     *       server: T|string|array<mixed>,
     *       get: T|string|array<mixed>,
     *       post: T|string|array<mixed>,
     *       files: T|string|array<mixed>,
     *       cookie: T|string|array<mixed>,
     *       session: T|string|array<mixed>,
     *       request: T|string|array<mixed>,
     *       env: T|string|array<mixed>
     *   } $globals
     */
    public function __construct(ContainerInterface $container, array $globals = [])
    {
        /** @var array<string, mixed> $getGlobals */
        $getGlobals = $globals['get'] ?? [];
        /** @var array<string, mixed> $postGlobals */
        $postGlobals = $globals['post'] ?? [];
        /** @var array<string, mixed> $serverGlobals */
        $serverGlobals = $globals['server'] ?? [];
        /** @var array<string, mixed> $filesGlobals */
        $filesGlobals = $globals['files'] ?? [];
        /** @var array<string, mixed> $cookieGlobals */
        $cookieGlobals = $globals['cookie'] ?? [];
        /** @var array<string, mixed> $sessionGlobals */
        $sessionGlobals = $globals['session'] ?? [];
        /** @var array<string, mixed> $envGlobals */
        $envGlobals = $globals['env'] ?? [];
        $newGlobals = [
            'server' => $serverGlobals,
            'get' => $getGlobals,
            'post' => $postGlobals,
            'files' => $filesGlobals,
            'cookie' => $cookieGlobals,
            'session' => $sessionGlobals,
            'request' => $getGlobals,
            'env' => $envGlobals,
        ];
        $this->configure(
            container: $container,
            globals: $newGlobals,
        );
    }
}
