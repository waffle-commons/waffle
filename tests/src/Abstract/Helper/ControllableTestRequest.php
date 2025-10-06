<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Throwable;
use Waffle\Interface\ResponseInterface;

/**
 * A specialized TestRequest that allows controlling the outcome of the `process` method for testing.
 * This class is fully initialized and can be configured to return a specific response or throw an exception.
 */
class ControllableTestRequest extends TestRequest
{
    private null|ResponseInterface $responseToReturn = null;
    private null|Throwable $exceptionToThrow = null;

    public function setResponse(ResponseInterface $response): void
    {
        $this->responseToReturn = $response;
    }

    public function setException(Throwable $e): void
    {
        $this->exceptionToThrow = $e;
    }

    /**
     * @throws Throwable
     */
    public function process(): ResponseInterface
    {
        if (null !== $this->exceptionToThrow) {
            throw $this->exceptionToThrow;
        }

        return $this->responseToReturn ?? parent::process();
    }
}
