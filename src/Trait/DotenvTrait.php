<?php

declare(strict_types=1);

namespace Waffle\Trait;

use Symfony\Component\Dotenv\Dotenv;

trait DotenvTrait
{
    public function loadEnv(bool $tests = false): void
    {
        $dotenv = new Dotenv();
        $test = $tests ? '.test' : '';
        $dotenv->loadEnv(path: APP_ROOT . DIRECTORY_SEPARATOR . ".env{$test}");
    }
}
