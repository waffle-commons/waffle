<?php

declare(strict_types=1);

use App\Kernel;

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', realpath(path: dirname(path: __DIR__)));

// The DummyController is a test helper, so we include it manually.
require_once __DIR__ . '/src/Abstract/Helper/TestRequest.php';
require_once __DIR__ . '/src/Abstract/Helper/TestCli.php';
require_once __DIR__ . '/src/Abstract/Helper/ConcreteTestResponse.php';
require_once __DIR__ . '/src/Router/Dummy/DummyController.php';
