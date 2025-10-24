<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Waffle\Abstract\AbstractResponse;
use Waffle\Core\View;
use Waffle\Interface\RequestInterface;

/**
 * A concrete implementation of AbstractResponse used for testing purposes.
 */
class ConcreteTestResponse extends AbstractResponse
{
    public function __construct(RequestInterface $handler)
    {
        $this->build(handler: $handler);
    }

    public function getView(): null|View
    {
        return new View(data: [
            'id' => 1,
            'name' => 'John Doe',
        ]);
    }
}
