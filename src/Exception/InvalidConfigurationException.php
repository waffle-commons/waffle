<?php

declare(strict_types=1);

namespace Waffle\Exception;

/**
 * Exception thrown when a configuration value is missing or has an invalid type.
 */
final class InvalidConfigurationException extends WaffleException
{
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
