<?php

declare(strict_types=1);

namespace Waffle\Interface;

interface ResponseInterface
{
    public function build(RequestInterface $handler): void;

    public function render(): void;
}
