<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Abstract\AbstractResponse;
use Waffle\Core\View;
use Waffle\Interface\CliInterface;
use Waffle\Interface\RequestInterface;

/**
 * A concrete implementation of AbstractResponse used for testing purposes.
 */
class ConcreteTestResponse extends AbstractResponse
{
    public function __construct(CliInterface|RequestInterface $handler)
    {
        $this->build(handler: $handler);
    }

    public function getView(): null|View
    {
        return $this->view;
    }

    public function isCli(): bool
    {
        return $this->cli;
    }
}
