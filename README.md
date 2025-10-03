Waffle Framework
================

A modern, minimalist, and security-focused PHP micro-framework designed for building fast and reliable JSON APIs. Waffle is built with the latest PHP features and a strong emphasis on a clean, robust, and fully-tested codebase.

Philosophy
----------

Waffle is designed around a few core principles:

*   **Modern PHP:** Leverages modern PHP features like Attributes, readonly properties, and strict typing to create a robust and maintainable codebase.

*   **Minimalist Core:** Provides only the essential components to handle HTTP requests and responses, without unnecessary bloat.

*   **DevSecOps First:** A complete, out-of-the-box CI/CD pipeline with static analysis, security auditing, and comprehensive testing is a core feature, not an afterthought.

*   **Developer Experience:** Simple, intuitive, and easy to get started with.


Features
--------

*   **Kernel-based Architecture:** A simple and powerful kernel handles the application lifecycle.

*   **Attribute-based Routing:** Define your routes directly on your controller classes and methods using PHP 8 attributes.

*   **Robust Security Layer:** An integrated security component that analyzes your code against configurable security levels.

*   **Automated CI/CD Pipeline:** Comes with a pre-configured GitHub Actions workflow that includes:

    *   **PHPUnit:** For unit and integration testing.

    *   **PHPStan & Psalm:** For high-level static analysis.

    *   **PHPCS:** For coding standards enforcement (PSR-12).

    *   **Composer Audit:** For security analysis of dependencies (SCA).

    *   **Psalm Taint Analysis:** For static application security testing (SAST).

    *   **Codecov:** For code coverage reporting.

*   **PSR Compliant:** Follows industry standards for maximum interoperability.


Getting Started
---------------

### Requirements

*   PHP 8.4 or higher

*   Composer


### Installation

Create a new project and require Waffle framework:

```shell
composer require eightyfour/waffle
```

Quick Start: "Hello World"
--------------------------

Let's build a simple API that returns a JSON message.

**1\. Directory Structure**

Create the following directory structure for your application:

```
  .  
  ├── app/  
  │   ├── Controller/  
  │   │   └── HomeController.php  
  │   ├── Config.php  
  │   └── Kernel.php  
  ├── public/  
  │   └── index.php  
  └── composer.json   
  
 ```

**2\. composer.json**

```json
{      
  "name": "your-vendor/your-app",      
  "autoload": {          
    "psr-4": {              
      "App\\": "app/"
    }
  },      
  "require": {
    "eightyfour/waffle": "dev-main"
  }
}   

```

**3\. Entrypoint (public/index.php)**

This is the only file exposed to the web. It creates the Kernel and tells it to handle the request.



```php
<?php declare(strict_types=1);  

require_once dirname(__DIR__) . '/vendor/autoload.php';  

define('APP_ROOT', realpath(dirname(__DIR__)));  

$kernel = new App\Kernel();  
$kernel->handle();   

```

**4\. Application Kernel (app/Kernel.php)**

The application kernel extends the base Waffle kernel and registers the application's configuration.



```php
<?php 

declare(strict_types=1);  

namespace App;  

use Waffle\Kernel as BaseKernel;  
use Override;  

final class Kernel extends BaseKernel  
{      
    #[Override]      
    public function boot(): self      
    {          
        $this->config = new Config();          
        return $this;      
    }  
}   

```

**5\. Application Configuration (app/Config.php)**

This class uses an attribute to tell the framework where to find controllers and what security level to apply.



```php
<?php

declare(strict_types=1);  

namespace App;  

use Waffle\Attribute\Configuration;  

#[Configuration(      
    controller: 'app/Controller',      
    securityLevel: 1  
)]  
final class Config extends Configuration  {}   

```

**6\. Home Controller (app/Controller/HomeController.php)**

This is where you define your application logic. Use the #\[Route\] attribute to define your endpoints.



```php
<?php

declare(strict_types=1);  

namespace App\Controller;  

use Waffle\Attribute\Route;  
use Waffle\Core\BaseController;  
use Waffle\Core\View;  

#[Route(path: '/', name: 'home_')]  
final class HomeController extends BaseController  
{
    #[Route(path: '', name: 'index')]      
    public function index(): View      
    {
        return new View(data: ['message' => 'Hello, Waffle!']);      
    }  
}   

```

That's it! Your API is ready. When you visit the root URL, you will get the following JSON response:

```json   
{
  "data": {
    "message": "Hello, Waffle!"
  }
}   

```

Testing
-------

Waffle comes with a complete testing suite. To run the tests for the framework itself:

`   composer tests   `

Contributing
------------

Contributions are welcome! Please feel free to submit a pull request or create an issue.

License
-------

This project is proprietary.