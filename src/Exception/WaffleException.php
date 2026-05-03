<?php

declare(strict_types=1);

namespace Waffle\Exception;

use Exception;
use Throwable;

class WaffleException extends Exception
{
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
