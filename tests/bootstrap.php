<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', realpath(path: dirname(path: __DIR__)));
const APP_CONFIG = 'temp_config';

require_once __DIR__ . '/src/AbstractTestCase.php';

// required test helpers, so we include them manually.
require_once __DIR__ . '/src/Helper/Service/TempService.php';
require_once __DIR__ . '/src/Helper/Controller/TempController.php';
require_once __DIR__ . '/src/Trait/Helper/AbstractService.php';
require_once __DIR__ . '/src/Helper/Service/NonReadOnlyService.php';
require_once __DIR__ . '/src/Abstract/Helper/ConcreteTestKernel.php';
require_once __DIR__ . '/src/Abstract/Helper/TestConfig.php';
require_once __DIR__ . '/src/Abstract/Helper/WebKernel.php';
require_once __DIR__ . '/src/Core/Helper/DummyControllerWithService.php';
require_once __DIR__ . '/src/Core/Helper/DummyService.php';
require_once __DIR__ . '/src/Core/Helper/ServiceA.php';
require_once __DIR__ . '/src/Core/Helper/ServiceB.php';
require_once __DIR__ . '/src/Core/Helper/ServiceC.php';
require_once __DIR__ . '/src/Core/Helper/ServiceD.php';
require_once __DIR__ . '/src/Core/Helper/ServiceE.php';
require_once __DIR__ . '/src/Core/Helper/AbstractUninstantiable.php';
require_once __DIR__ . '/src/Core/Helper/WithPrimitive.php';
require_once __DIR__ . '/src/Core/Helper/TestKernelWithConfig.php';
require_once __DIR__ . '/src/Trait/Helper/DuplicateRouteController.php';
require_once __DIR__ . '/src/Trait/Helper/TraitObject.php';
require_once __DIR__ . '/src/Trait/Helper/UninitializedPropertyClass.php';
