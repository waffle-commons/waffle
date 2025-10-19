<?php

declare(strict_types=1);

// 1. Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load our custom TestKernel
use App\TestKernel as Kernel;

// 3. Instantiate constants to boot the framework
define('APP_ROOT', realpath(path: dirname(path: __DIR__)));
const APP_CONFIG = 'public/app/config';

// 4. Handle the incoming HTTP request and send the response
new Kernel()->handle();
