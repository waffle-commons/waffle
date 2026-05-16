<?php

declare(strict_types=1);

namespace WaffleTests\Helper\Dto;

use Waffle\Commons\Contracts\Attribute\Dto;

/**
 * DTO without a constructor — exercises the "no constructor" branch of the resolver.
 */
#[Dto]
final readonly class EmptyDto {}
