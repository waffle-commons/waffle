<?php

declare(strict_types=1);

namespace Waffle\Enum;

use Waffle\Core\Constant;

enum AppMode: string
{
    case WEB = Constant::WEB;
    case CLI = Constant::CLI;
}
