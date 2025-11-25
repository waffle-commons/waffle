<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

use Waffle\Attribute\Route;

#[Route('/duplicate-test')]
final class DuplicateRouteController
{
    #[Route('/same-path', name: 'first_method')]
    public function first(): string
    {
        return 'first';
    }

    #[Route('/same-path', name: 'second_method')]
    public function second(): string
    {
        return 'second';
    }
}
