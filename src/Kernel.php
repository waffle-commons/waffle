<?php

declare(strict_types=1);

namespace Waffle;

use Waffle\Abstract\AbstractKernel;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Kernel extends AbstractKernel
{
    public function __construct() {
        $this->boot();
    }
}
