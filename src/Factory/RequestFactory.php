<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Waffle\Core\Constant;
use Waffle\Core\Request;
use Waffle\Core\System;
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
        $postData = isset($_POST) ? $_POST : [];
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        if (str_contains(strtolower($contentType), 'application/json')) {
            $json = file_get_contents('php://input');
            if ($json) {
                /**
                 * @template T
                 * @var T|string|array<mixed> $postData
                 */
                $postData = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            }
        }
        $serverReq = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        $reqMethod = match ($serverReq) {
            Constant::METHOD_POST => $postData,
            default => $_GET,
        };

        /**
         * @template T
         * @var array{
         *        server: T|string|array<mixed>,
         *        get: T|string|array<mixed>,
         *        post: T|string|array<mixed>,
         *        files: T|string|array<mixed>,
         *        cookie: T|string|array<mixed>,
         *        session: T|string|array<mixed>,
         *        request: T|string|array<mixed>,
         *        env: T|string|array<mixed>
         *    } $globals
         */
        $globals = [
            'server' => isset($_SERVER) ? $_SERVER : [],
            'get' => $_GET,
            'post' => $postData,
            'files' => $_FILES,
            'cookie' => $_COOKIE ?? [],
            'session' => isset($_SESSION) ? $_SESSION : [],
            'request' => $reqMethod,
            'env' => $_ENV,
        ];
        $req = new Request(
            container: $container,
            globals: $globals,
        );
        $router = $system->getRouter();
        if (null !== $router) {
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
