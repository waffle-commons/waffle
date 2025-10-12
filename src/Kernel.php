<?php

declare(strict_types=1);

namespace Waffle;

use Waffle\Abstract\AbstractKernel;

class Kernel extends AbstractKernel
{
    public function __construct()
    {
        $this->boot();
    }
}
