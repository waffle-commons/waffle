<?php

declare(strict_types=1);

namespace Waffle\Enum;

use Waffle\Core\Constant;

enum Failsafe: string
{
    case ENABLED = Constant::ENABLED;
    case DISABLED = Constant::DISABLED;
}
