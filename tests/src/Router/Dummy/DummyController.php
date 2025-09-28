<?php

declare(strict_types=1);

namespace WaffleTests\Router\Dummy;

use Waffle\Attribute\Route;
use Waffle\Core\BaseController;
use Waffle\Core\View;

/**
 * This is a fake controller used exclusively for testing purposes.
 * It helps validate the Router and Response logic without depending on real application code.
 */
#[Route(path: '/users', name: 'user')]
final class DummyController extends BaseController
{
    #[Route(path: '', name: 'list')]
    public function list(): View
    {
        return new View(['message' => 'Dummy controller list action']);
    }

    #[Route(path: '/{id}', name: 'show')]
    public function show(int $id): View
    {
        return new View(['id' => $id]);
    }
}
