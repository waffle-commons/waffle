<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Waffle\Core\Constant;
use Waffle\Exception\SecurityException;
use Waffle\Interface\SecurityInterface;
use Waffle\Trait\SecurityTrait;

abstract class AbstractSecurity implements SecurityInterface
{
    use SecurityTrait;

    protected(set) int $level
        {
            set => $this->level = $value;
        }

    abstract public function __construct(object $config);

    /**
     * @param object $object
     * @param string[] $expectations
     * @return void
     * @throws SecurityException
     */
    public function analyze(object $object, array $expectations = []): void
    {
        $className = get_class(object: $object);
        $expects = implode(separator: Constant::ARRAY_SEPARATOR_ALL, array: $expectations);
        if (!$this->isValid(object: $object, expectations: $expectations)) {
            throw new SecurityException(
                message: "The object $className is not valid. It is not an instance of $expects.",
                code: 500
            );
        }
        if (!$this->isSecure(object: $object, level: $this->level)) {
            throw new SecurityException(
                message: "The object $className is not secure.",
                code: 500
            );
        }
    }
}
