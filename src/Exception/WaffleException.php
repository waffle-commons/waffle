<?php

namespace Waffle\Exception;

use Waffle\Trait\RenderingTrait;
use Exception;
use Throwable;

class WaffleException extends Exception
{
    use RenderingTrait;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array{
     *      message: string,
     *      code: int,
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
