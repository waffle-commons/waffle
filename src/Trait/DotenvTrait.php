<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Symfony\Component\Dotenv\Dotenv;

trait DotenvTrait
{
    public function loadEnv(bool $tests = false): void
    {
        /** @var string $root */
        $root = APP_ROOT;
        $dotenv = new Dotenv();
        $test = $tests ? '.test' : '';
        $dotenv->loadEnv(path: $root . DIRECTORY_SEPARATOR . ".env{$test}");
    }
}
