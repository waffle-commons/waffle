<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', realpath(path: dirname(path: __DIR__)));
const APP_CONFIG = 'temp_config';

require_once __DIR__ . '/src/TestCase.php';

// The DummyController is a test helper, so we include it manually.
require_once __DIR__ . '/src/Helper/TempService.php';
require_once __DIR__ . '/src/Helper/TempController.php';
require_once __DIR__ . '/src/Trait/Helper/Service.php';
require_once __DIR__ . '/src/Helper/NonReadOnlyService.php';
require_once __DIR__ . '/src/Abstract/Helper/TestRequest.php';
require_once __DIR__ . '/src/Abstract/Helper/TestCli.php';
require_once __DIR__ . '/src/Abstract/Helper/ConcreteTestResponse.php';
require_once __DIR__ . '/src/Abstract/Helper/ConcreteTestKernel.php';
require_once __DIR__ . '/src/Abstract/Helper/ConcreteTestRequest.php';
require_once __DIR__ . '/src/Abstract/Helper/ConcreteTestCli.php';
require_once __DIR__ . '/src/Abstract/Helper/ConcreteTestSecurity.php';
require_once __DIR__ . '/src/Abstract/Helper/ControllableTestRequest.php';
require_once __DIR__ . '/src/Abstract/Helper/TestConfig.php';
require_once __DIR__ . '/src/Abstract/Helper/WebKernel.php';
require_once __DIR__ . '/src/Core/Helper/DummyControllerWithService.php';
require_once __DIR__ . '/src/Core/Helper/DummyService.php';
require_once __DIR__ . '/src/Core/Helper/ServiceA.php';
require_once __DIR__ . '/src/Core/Helper/ServiceB.php';
require_once __DIR__ . '/src/Core/Helper/ServiceC.php';
require_once __DIR__ . '/src/Core/Helper/ServiceD.php';
require_once __DIR__ . '/src/Core/Helper/ServiceE.php';
require_once __DIR__ . '/src/Core/Helper/Uninstantiable.php';
require_once __DIR__ . '/src/Core/Helper/WithPrimitive.php';
require_once __DIR__ . '/src/Core/Helper/TestKernelWithConfig.php';
require_once __DIR__ . '/src/Router/Dummy/DummyController.php';
require_once __DIR__ . '/src/Trait/Helper/DummyAttribute.php';
require_once __DIR__ . '/src/Trait/Helper/DummyClassWithAttribute.php';
require_once __DIR__ . '/src/Trait/Helper/FinalReadOnlyClass.php';
require_once __DIR__ . '/src/Trait/Helper/NonFinalTestController.php';
require_once __DIR__ . '/src/Trait/Helper/TraitObject.php';
require_once __DIR__ . '/src/Trait/Helper/UninitializedPropertyClass.php';
