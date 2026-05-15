<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use Waffle\Commons\Contracts\Attribute\Dto;
use Waffle\Exception\ValidationException;

/**
 * Canonical Phase 2 DTO sample: validation lives inside Property Hooks (RFC-011).
 * The object cannot be constructed in an invalid state — there is no separate
 * validate() step.
 *
 * Note: PHP 8.5 forbids `readonly` on hooked properties, so the class itself is
 * non-readonly. Property Hooks enforce the invariant by guarding every write.
 */
#[Dto]
final class UserRegistrationDto
{
    public string $email {
        set(string $value) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException(message: sprintf('Invalid email format: "%s".', $value), field: 'email');
            }
            $this->email = strtolower($value);
        }
    }

    public int $age {
        set(int $value) {
            if ($value < 0 || $value > 150) {
                throw new ValidationException(
                    message: sprintf('Age must be between 0 and 150, got %d.', $value),
                    field: 'age',
                );
            }
            $this->age = $value;
        }
    }

    public function __construct(string $email, int $age)
    {
        $this->email = $email;
        $this->age = $age;
    }
}
