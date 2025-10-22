<?php

declare(strict_types=1);

namespace App\Service;

class HomeService
{
    /**
     * @return string[]
     */
    public function sayHello(): array
    {
        return [
            'message' => 'Hello from Waffle!',
        ];
    }
}
