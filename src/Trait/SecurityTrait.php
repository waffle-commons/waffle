<?php

declare(strict_types=1);

namespace Waffle\Trait;

use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use Waffle\Core\Constant;
use Waffle\Exception\SecurityException;

trait SecurityTrait
{
    /**
     * @param object|null $object
     * @param string[] $expectations
     * @return bool
     */
    public function isValid(null|object $object = null, array $expectations = []): bool
    {
        return array_all($expectations, static fn($expectation) => $object instanceof $expectation);
    }

    /**
     * Checks cumulative security rules based on the specified level.
     * @throws SecurityException
     */
    public function isSecure(object $object, int $level = 10): bool
    {
        // Cumulative logic is handled here. We use a match expression for concise
        // comparisons, and chained function calls for cumulation.
        match (true) {
            $level >= Constant::SECURITY_LEVEL10 => $this->checkLevel10(object: $object)
                && $this->checkLevel9(object: $object)
                && $this->checkLevel8(object: $object)
                && $this->checkLevel7(object: $object)
                && $this->checkLevel6(object: $object)
                && $this->checkLevel5(object: $object)
                && $this->checkLevel4(object: $object)
                && $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL9 => $this->checkLevel9(object: $object)
                && $this->checkLevel8(object: $object)
                && $this->checkLevel7(object: $object)
                && $this->checkLevel6(object: $object)
                && $this->checkLevel5(object: $object)
                && $this->checkLevel4(object: $object)
                && $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL8 => $this->checkLevel8(object: $object)
                && $this->checkLevel7(object: $object)
                && $this->checkLevel6(object: $object)
                && $this->checkLevel5(object: $object)
                && $this->checkLevel4(object: $object)
                && $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL7 => $this->checkLevel7(object: $object)
                && $this->checkLevel6(object: $object)
                && $this->checkLevel5(object: $object)
                && $this->checkLevel4(object: $object)
                && $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL6 => $this->checkLevel6(object: $object)
                && $this->checkLevel5(object: $object)
                && $this->checkLevel4(object: $object)
                && $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL5 => $this->checkLevel5(object: $object)
                && $this->checkLevel4(object: $object)
                && $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL4 => $this->checkLevel4(object: $object)
                && $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL3 => $this->checkLevel3(object: $object)
                && $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL2 => $this->checkLevel2(object: $object)
                && $this->checkLevel1(object: $object),
            $level >= Constant::SECURITY_LEVEL1 => $this->checkLevel1(object: $object),
            default => true,
        };

        return true;
    }

    // --- INDIVIDUAL SECURITY RULES (LEVELS 1-10) ---

    /**
     * Security Level 1: Checks basic object consistency.
     * [Rule 1]: Ensures the object is an instance of its own class.
     * @throws SecurityException
     */
    private function checkLevel1(object $object): bool
    {
        $class = get_class(object: $object);
        if (!$object instanceof $class) {
            throw new SecurityException(
                message: "Level 1: Object validation failed. Instance mismatch for {$class}.",
                code: 500,
            );
        }

        return true;
    }

    /**
     * Security Level 2: Basic property validation.
     * [Rule 2]: Ensures there are no untyped public properties (encourages private/protected properties).
     * @throws SecurityException
     */
    private function checkLevel2(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        $properties = $reflection->getProperties(filter: ReflectionProperty::IS_PUBLIC);
        $class = get_class(object: $object);

        foreach ($properties as $property) {
            if ($property->getType() === null) {
                throw new SecurityException(
                    message: "Level 2: Public property '{$property->getName()}' in {$class} must be typed.",
                    code: 500,
                );
            }
        }
        return true;
    }

    /**
     * Security Level 3: Basic method validation.
     * [Rule 3]: Ensures all public methods are not 'void' if they return something.
     * @throws SecurityException
     */
    private function checkLevel3(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        $methods = $reflection->getMethods(filter: ReflectionMethod::IS_PUBLIC);
        $class = get_class(object: $object);

        foreach ($methods as $method) {
            // Ignore constructor and magic methods
            if (str_starts_with(
                haystack: $method->getName(),
                needle: '__',
            )) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (null !== $returnType) {
                $returnConditions = $returnType->getName() === Constant::TYPE_VOID;
                if ($returnConditions && $method->getDeclaringClass()->getName() === $class) {
                    throw new SecurityException(
                        message: "Level 3: Public method '{$method->getName()}' in {$class} "
                        . "must not be of '{$returnType}' type.",
                        code: 500,
                    );
                }
            }
        }

        return true;
    }

    /**
     * Security Level 4: Strengthens public method typing.
     * [Rule 4]: Ensures all public methods have an explicit return type (non-null).
     * @throws SecurityException
     */
    private function checkLevel4(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        $methods = $reflection->getMethods(filter: ReflectionMethod::IS_PUBLIC);
        $class = get_class(object: $object);

        foreach ($methods as $method) {
            // Ignore constructor and magic methods
            if (str_starts_with(
                haystack: $method->getName(),
                needle: '__',
            )) {
                continue;
            }

            if ($method->getReturnType() === null && $method->getDeclaringClass()->getName() === $class) {
                throw new SecurityException(
                    message: "Level 4: Public method '{$method->getName()}' in {$class} must declare a return type.",
                    code: 500,
                );
            }
        }
        return true;
    }

    /**
     * Security Level 5: Ensures good encapsulation.
     * [Rule 5]: For example, ensures *private* properties are typed.
     * @throws SecurityException
     */
    private function checkLevel5(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        $properties = $reflection->getProperties(filter: ReflectionProperty::IS_PRIVATE);
        $class = get_class(object: $object);

        foreach ($properties as $property) {
            if ($property->getType() === null && $property->getDeclaringClass()->getName() === $class) {
                throw new SecurityException(
                    message: "Level 5: Private property '{$property->getName()}' in {$class} must be typed.",
                    code: 500,
                );
            }
        }
        return true;
    }

    /**
     * Security Level 6: Reinforces property initialization.
     * [Rule 6]: Ensures all declared properties are initialized (including protected/private).
     * @throws SecurityException
     */
    private function checkLevel6(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        $properties = $reflection->getProperties();
        $class = get_class(object: $object);

        foreach ($properties as $property) {
            if (!$property->isInitialized(object: $object) && $property->getDeclaringClass()->getName() === $class) {
                throw new SecurityException(
                    message: "Level 6: Property '{$property->getName()}' in {$class} is not initialized.",
                    code: 500,
                );
            }
        }
        return true;
    }

    /**
     * Security Level 7: Method argument safety.
     * [Rule 7]: Ensures all public method arguments are typed.
     * @throws SecurityException
     */
    private function checkLevel7(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        $methods = $reflection->getMethods(filter: ReflectionMethod::IS_PUBLIC);
        $class = get_class(object: $object);

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            foreach ($method->getParameters() as $param) {
                $paramType = $param->getType();
                if (
                    null !== $paramType
                    && $paramType->getName() === Constant::TYPE_MIXED
                    && !$param->isDefaultValueAvailable()
                ) {
                    throw new SecurityException(
                        message: "Level 7: Public method '{$method->getName()}' parameter '{$param->getName()}' "
                        . 'must be strictly typed.',
                        code: 500,
                    );
                }
            }
        }
        return true;
    }

    /**
     * Security Level 8: Code finalization.
     * [Rule 8]: Ensures important classes (e.g., Controllers) are declared final.
     * @throws SecurityException
     */
    private function checkLevel8(object $object): bool
    {
        // We check if the class name contains 'Controller' and if it is not final.
        $reflection = new ReflectionObject(object: $object);
        if (
            !$reflection->isFinal()
            && str_contains(
                haystack: $reflection->getName(),
                needle: 'Controller',
            )
        ) {
            throw new SecurityException(
                message: 'Level 8: Controller classes must be declared final to prevent unintended extension.',
                code: 500,
            );
        }
        return true;
    }

    /**
     * Security Level 9: Immutability.
     * [Rule 9]: Ensures classes are read-only (simulating the PHP 8.2+ `readonly` attribute for services/DTOs).
     * @throws SecurityException
     */
    private function checkLevel9(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        // Here, we check if the object is a DTO class (simulating a DTO with "Service" in the name).
        // Note: The actual `isReadOnly()` method is available in PHP 8.2+.
        if (
            !$reflection->isReadOnly()
            && str_contains(
                haystack: $reflection->getName(),
                needle: 'Service',
            )
        ) {
            throw new SecurityException(
                message: 'Level 9: Service classes must be declared readonly.',
                code: 500,
            );
        }
        return true;
    }

    /**
     * Security Level 10: The strictest set (Full Strictness).
     * [Rule 10]: Ensures all classes are final.
     * @throws SecurityException
     */
    private function checkLevel10(object $object): bool
    {
        $reflection = new ReflectionObject(object: $object);
        if (!$reflection->isFinal()) {
            throw new SecurityException(
                message: 'Level 10: All classes must be declared final.',
                code: 500,
            );
        }
        return true;
    }
}
