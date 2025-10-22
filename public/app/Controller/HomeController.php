<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HomeService;
use Waffle\Attribute\Argument;
use Waffle\Attribute\Route;
use Waffle\Core\BaseController;
use Waffle\Core\View;

/**
 * This is a simple template controller to test the end-to-end
 * request/response lifecycle of the Waffle framework.
 */
#[Route(path: '/', name: 'home_')]
final class HomeController extends BaseController
{
    /**
     * Handles requests to the root path ("/").
     */
    #[Route(path: '', name: 'index')]
    public function index(HomeService $service): View
    {
        return new View(data: $service->sayHello());
    }

    /**
     * Handles dynamic requests to "/hello/{name}".
     * This tests the router's ability to handle parameters.
     */
    #[Route(
        path: '/hello/{name}',
        name: 'hello',
        arguments: [
            new Argument(classType: 'string', paramName: 'name', required: false),
        ],
    )]
    public function hello(string $name): View
    {
        return new View(data: ['message' => sprintf('Hello, %s!', $name)]);
    }
}
