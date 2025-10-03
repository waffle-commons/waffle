<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

#[DummyAttribute(value: 'test-value')]
class DummyClassWithAttribute
{
    public function publicMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
    }
}
