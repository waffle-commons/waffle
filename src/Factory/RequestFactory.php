<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Core\System;
use Waffle\Enum\AppMode;
use Waffle\Exception\SecurityException;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\RequestInterface;

class RequestFactory
{
    /**
     * Creates a Request object from PHP's superglobals.
     * This is the single point in the application with direct access to these globals.
     *
     * @throws SecurityException
     */
    public function createFromGlobals(ContainerInterface $container, System $system): RequestInterface
    {
        $reqMethod = match ($_SERVER['REQUEST_METHOD']) {
            Constant::METHOD_POST => $_POST ?? [],
            default => $_GET ?? [],
        };
        $globals = [
            'server' => $_SERVER ?? [],
            'get' => $_GET ?? [],
            'post' => $_POST ?? [],
            'files' => $_FILES ?? [],
            'cookie' => $_COOKIE ?? [],
            'session' => $_SESSION ?? [],
            'request' => $reqMethod,
            'env' => $_ENV ?? [],
        ];
        $req = new Request(
            container: $container,
            cli: AppMode::WEB,
            globals: $globals,
        );
        $router = $system->getRouter();
        if (null !== $router && !$req->isCli()) {
            $routes = $router->getRoutes();
            /**
             * @var array{
             *      classname: string,
             *      method: non-empty-string,
             *      arguments: array<non-empty-string, string>,
             *      path: string,
             *      name: non-falsy-string
             *  } $route
             */
            foreach ($routes as $route) {
                if ($router->match(
                    container: $container,
                    req: $req,
                    route: $route,
                )) {
                    $req->setCurrentRoute(route: $route);
                    break; // Stop after the first match
                }
            }
        }

        return $req;
    }
}
