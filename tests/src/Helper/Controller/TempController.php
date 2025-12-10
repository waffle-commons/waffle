<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Controller;

use Exception;
use Waffle\Core\BaseController;
use Waffle\Core\View;

/**
 * This is a fake controller used exclusively for testing purposes.
 * It helps validate the Router and Response logic without depending on real application code.
 */
final class TempController extends BaseController
{
    public function list(): View
    {
        return new View(data: [
            'id' => 1,
            'name' => 'John Doe',
        ]);
    }

    public function show(int $id): View
    {
        return new View([
            'id' => $id,
            'name' => 'John Doe',
        ]);
    }

    public function details(int $id, string $slug): View
    {
        return new View(data: ['id' => $id, 'slug' => $slug]);
    }

    public function profile(): View
    {
        return new View(data: ['page' => 'profile']);
    }

    /**
     * @throws Exception
     */
    public function throwError(): void
    {
        throw new Exception('Something went wrong');
    }
}
