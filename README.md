[![PHP Version Require](http://poser.pugx.org/waffle-commons/waffle/require/php)](https://packagist.org/packages/waffle-commons/waffle)
[![PHP CI](https://github.com/waffle-commons/waffle/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/waffle/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/waffle/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/waffle)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/waffle/v)](https://packagist.org/packages/waffle-commons/waffle)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/waffle/v/unstable)](https://packagist.org/packages/waffle-commons/waffle)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/waffle.svg)](https://packagist.org/packages/waffle-commons/waffle)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/waffle)](https://github.com/waffle-commons/waffle/blob/main/LICENSE.md)

Waffle Framework
================

A modern, minimalist, and security-focused PHP micro-framework designed for building fast and reliable JSON APIs. Waffle is built with the latest PHP features (8.4+) and follows PSR standards strictly.

Philosophy
----------

Waffle is designed around a few core principles:

*   **Modern PHP:** Leverages Attributes, Readonly properties, and strict typing.

*   **Standards First:** Fully PSR-7 (HTTP Message), PSR-11 (Container), and PSR-17 (HTTP Factory) compliant.

*   **Security by Design:** Integrated security layer analyzing code structure against configurable levels.

*   **Decoupled Architecture:** The core logic is separated from infrastructure (HTTP, Container implementation), making it robust and testable.


Architecture
------------

Waffle adopts a modular architecture where the Core ("The Brain") is separated from the Runtime ("The Glue").

*   **Waffle Core (`waffle-commons/waffle`):** Contains the Kernel, Router, Security Layer, and Abstract Controllers. It defines interfaces but does not implement the low-level plumbing.

*   **Waffle Runtime (`waffle-commons/runtime`):** Handles the request lifecycle, connecting the HTTP layer and the Container to the Core.

*   **Commons Components:** Standalone PSR implementations for `http` and `container`.


Getting Started
---------------

### Installation

The recommended way to start a new Waffle project is to install the Runtime, which pulls in the Core and necessary components.

```shell
composer require waffle-commons/runtime
```

### Directory Structure

A typical Waffle application structure (as managed in your workspace):

```text
.
├── app/
│   ├── Controller/   # Your API Controllers
│   ├── Service/      # Business Logic Services
│   └── Kernel.php    # Your Application Kernel
├── config/
│   ├── app.yaml      # Main configuration
│   └── app_prod.yaml # Environment specific overrides
├── public/
│   └── index.php     # Entry point
└── composer.json
```

### Usage Example

The Kernel now requires the Runtime component to start the request lifecycle.

**1\. Create your Kernel (`app/Kernel.php`):**

```php
namespace App;

use Waffle\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    // Custom boot logic or service registration goes here
}
```

**2\. Create a Controller (`app/Controller/HelloController.php`):**

```php
namespace App\Controller;

use Waffle\Attribute\Route;
use Waffle\Core\BaseController;
use Waffle\Core\View;

#[Route('/hello', name: 'hello_')]
final class HelloController extends BaseController
{
    #[Route('/{name}', name: 'world')]
    public function world(string $name): View
    {
        return new View(data: ['message' => "Hello $name!"]);
    }
}
```

// 3. Entry Point (`public/index.php`) - Uses the Runtime:

```php
use Waffle\Commons\Config\Config;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Http\Emitter\ResponseEmitter;
use Waffle\Commons\Http\Factory\GlobalsFactory;
use Waffle\Commons\Runtime\WaffleRuntime;
use Waffle\Commons\Security\Security;
use App\Kernel;

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', dirname(__DIR__));

// 1. Setup Dependencies
$config = new Config(APP_ROOT . '/config', 'prod');
$security = new Security($config);
$container = new Container();

// 2. Setup Kernel
$kernel = new Kernel();
$kernel->setConfiguration($config);
$kernel->setSecurity($security);
$kernel->setContainerImplementation($container);

// 3. Create Request & Emitter
$request = (new GlobalsFactory())->createServerRequestFromGlobals();
$emitter = new ResponseEmitter();

// 4. Run via Runtime
$runtime = new WaffleRuntime();
$runtime->run($kernel, $request, $emitter);
```

Testing
-------

To run the tests, use the following command:

```bash
composer tests
```

Contributing
------------

Contributions are welcome! Please refer to [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

License
-------

This project is licensed under the MIT License. See the [LICENSE.md](./LICENSE.md) file for details.
