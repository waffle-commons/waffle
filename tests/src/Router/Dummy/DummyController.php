<?php

declare(strict_types=1);

namespace WaffleTests\Router\Dummy;

use Exception;
use Waffle\Attribute\Route;
use Waffle\Core\BaseController;
use Waffle\Core\View;

/**
 * This is a fake controller used exclusively for testing purposes.
 * It helps validate the Router and Response logic without depending on real application code.
 */
#[Route(path: '/', name: 'user')]
final class DummyController extends BaseController
{
    #[Route(path: 'users', name: 'users_list')]
    public function list(): View
    {
        return new View([
            'id' => 1,
            'name' => 'John Doe',
        ]);
    }

    #[Route(path: 'users/{id}', name: 'users_show')]
    public function show(int $id): View
    {
        return new View([
            'id' => $id,
            'name' => 'John Doe',
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'trigger-error', name: 'trigger_error')]
    public function throwError(): void
    {
        throw new Exception('Something went wrong');
    }
}
