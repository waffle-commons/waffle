<?php

declare(strict_types=1);

namespace Waffle\Exception;

use Waffle\Commons\Contracts\Config\Exception\InvalidConfigurationExceptionInterface;

/**
 * Exception thrown when a configuration value is missing or has an invalid type.
 */
final class InvalidConfigurationException extends WaffleException implements InvalidConfigurationExceptionInterface
{
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
