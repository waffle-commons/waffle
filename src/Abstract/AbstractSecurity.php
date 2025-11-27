<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Commons\Config\Config;use Waffle\Commons\Contracts\Constant\Constant;use Waffle\Commons\Contracts\Security\SecurityInterface;use Waffle\Exception\SecurityException;use Waffle\Trait\SecurityTrait;

abstract class AbstractSecurity implements SecurityInterface
{
    use SecurityTrait;

    protected(set) int $level {
        set => $this->level = $value;
    }

    abstract public function __construct(Config $cfg);

    /**
     * @param object $object
     * @param string[] $expectations
     * @return void
     * @throws SecurityException
     */
    #[\Override]
    public function analyze(object $object, array $expectations = []): void
    {
        $className = get_class($object);
        $expects = implode(
            separator: Constant::ARRAY_SEPARATOR_ALL,
            array: $expectations,
        );
        if (!$this->isValid(
            object: $object,
            expectations: $expectations,
        )) {
            throw new SecurityException(
                message: "The object {$className} is not valid. It is not an instance of {$expects}.",
                code: 500,
            );
        }
        try {
            $this->isSecure(
                object: $object,
                level: $this->level,
            );
        } catch (SecurityException $exception) {
            throw new SecurityException(
                message: "The object {$className} is not secure.",
                code: 500,
                previous: $exception,
            );
        }
    }
}
