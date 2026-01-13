<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Controller;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Waffle\Core\BaseController;

/**
 * This is a fake controller used exclusively for testing purposes.
 * It helps validate the Router and Response logic without depending on real application code.
 */
final class TempController extends BaseController
{
    public function list(): ResponseInterface
    {
        return $this->jsonResponse(data: [
            'id' => 1,
            'name' => 'John Doe',
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        return $this->jsonResponse(data: [
            'id' => $id,
            'name' => 'John Doe',
        ]);
    }

    public function details(int $id, string $slug): ResponseInterface
    {
        return $this->jsonResponse(data: ['id' => $id, 'slug' => $slug]);
    }

    public function profile(): ResponseInterface
    {
        return $this->jsonResponse(data: ['page' => 'profile']);
    }

    /**
     * @throws Exception
     */
    public function throwError(): void
    {
        throw new Exception('Something went wrong');
    }
}
