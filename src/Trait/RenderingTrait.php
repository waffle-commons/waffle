<?php

declare(strict_types=1);

namespace Waffle\Trait;

use JsonException;
use Waffle\Core\Constant;
use Waffle\Core\View;
use Waffle\Exception\RenderingException;

trait RenderingTrait
{
    public function rendering(View $view, string $env): void
    {
        try {
            $json = json_encode($view, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            if ($env !== Constant::ENV_TEST) {
                header('Content-Type: application/json');
                echo $json;
            }
        } catch (JsonException $e) {
            throw new RenderingException(
                message: $e->getMessage(),
                code: (int) $e->getCode(),
                previous: $e->getPrevious(),
            );
        }
    }

    public function throw(View $view, null|string $env = null): void
    {
        $thrownEnv = match ($env) {
            Constant::ENV_TEST => Constant::ENV_TEST,
            default => Constant::ENV_EXCEPTION,
        };
        $this->rendering(
            view: $view,
            env: $thrownEnv,
        );
    }
}
