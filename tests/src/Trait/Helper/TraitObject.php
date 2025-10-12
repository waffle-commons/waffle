<?php

declare(strict_types=1);

namespace WaffleTests\Trait\Helper;

use Waffle\Core\View;
use Waffle\Trait\RenderingTrait;

class TraitObject
{
    use RenderingTrait;

    public function rendered(View $view, string $env): void
    {
        $this->rendering(
            view: $view,
            env: $env,
        );
    }
}
