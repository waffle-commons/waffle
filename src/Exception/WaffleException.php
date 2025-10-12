<?php

declare(strict_types=1);

namespace Waffle\Exception;

use Exception;
use Throwable;
use Waffle\Trait\RenderingTrait;

class WaffleException extends Exception
{
    use RenderingTrait;

    /**
     * @return array{
     *      message: string,
     *      code: int|string,
     *      previous: Throwable|null,
     *  }
     */
    public function serialize(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'previous' => $this->getPrevious(),
        ];
    }
}
