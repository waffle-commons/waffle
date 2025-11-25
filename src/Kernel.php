<?php

declare(strict_types=1);

namespace Waffle;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Waffle\Abstract\AbstractKernel;

class Kernel extends AbstractKernel
{
    public function __construct(LoggerInterface $logger = new NullLogger())
    {
        parent::__construct($logger);
        $this->boot();
    }
}
