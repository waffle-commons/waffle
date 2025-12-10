<?php

declare(strict_types=1);

namespace Waffle\Exception;

use Waffle\Commons\Contracts\Routing\Exception\RouteNotFoundExceptionInterface;

/**
 * Custom exception for handling 404 Not Found errors.
 */
final class RouteNotFoundException extends WaffleException implements RouteNotFoundExceptionInterface
{
    public function __construct(string $message = 'Route not found.', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
