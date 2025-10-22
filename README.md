[![PHP Version Require](http://poser.pugx.org/waffle-commons/waffle/require/php)](https://packagist.org/packages/waffle-commons/waffle)
[![PHP CI](https://github.com/waffle-commons/waffle/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/waffle/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/waffle/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/waffle)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/waffle/v)](https://packagist.org/packages/waffle-commons/waffle)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/waffle/v/unstable)](https://packagist.org/packages/waffle-commons/waffle)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/waffle.svg)](https://packagist.org/packages/waffle-commons/waffle)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/waffle)](https://github.com/waffle-commons/waffle/blob/main/LICENSE.md)

# Waffle Framework

A modern, minimalist, and security-focused PHP micro-framework designed for building fast and reliable JSON APIs. Waffle is built with the latest PHP features and a strong emphasis on a clean, robust, and fully-tested codebase.

## Philosophy

Waffle is designed around a few core principles:

 - **Modern PHP:** Leverages modern PHP features like Attributes, readonly properties, and strict typing to create a robust and maintainable codebase.

- **Minimalist Core:** Provides only the essential components to handle HTTP requests and responses, without unnecessary bloat.

- **DevSecOps First:** A complete, out-of-the-box CI/CD pipeline with static analysis, security auditing, and comprehensive testing is a core feature, not an afterthought.

- **Developer Experience:** Simple, intuitive, and easy to get started with.

## Features

 - **Modern Architecture:** A simple and powerful Kernel handles the application lifecycle.

 - **Dependency Injection Container:** A lightweight, powerful container for managing your services.

 - **Attribute-based Routing:** Define your API endpoints directly on your controller methods using PHP attributes.

 - **Robust Security Layer:** An integrated security component that analyzes your code against configurable security levels.

 - **Automated CI/CD Pipeline:** Comes with a pre-configured GitHub Actions workflow that includes:

   - **PHPUnit:** For unit and integration testing.

   - **Mago:** For high-level static analysis & coding standards enforcement.

   - **Composer Audit:** For security analysis of dependencies (SCA).

   - **Psalm Taint Analysis:** For static application security testing (SAST).

## Getting Started

### Requirements

 - PHP 8.4 or higher

 - Composer

### Installation

The best way to start a new project, for now, is by using the official `fuzzy` application skeleton:

```shell
composer create-project eightyfour/fuzzy my-api
```

This will set up a new project with a sensible directory structure and all the necessary configurations.

### Quick Start: "Hello World"

Let's see how simple it is to create a new endpoint.

#### 1. Create a Controller (`app/Controller/HomeController.php`)

This is where you define your application logic. Use the `#[Route]` attribute to define your endpoints.

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Waffle\Attribute\Route;
use Waffle\Core\BaseController;
use Waffle\Core\View;

#[Route(path: '/', name: 'home')]
final class HomeController extends BaseController
{
    #[Route(path: '', name: 'index')]
    public function index(): View
    {
        return new View(data: ['message' => 'Hello, Waffle!']);
    }
}
```


#### 2. Run your application

If you used the fuzzy skeleton, you can start the local development server with Docker:

```shell
make dev
```


That's it! Visit [http://localhost](http://localhost) in your browser. You will get the following JSON response:

```json
{
  "data": {
    "message": "Hello, Waffle!"
  }
}
```


## Testing

Waffle is built with testing in mind. To run the complete test suite for the framework itself:

```shell
composer tests
```

## Contributing

Contributions are welcome! Please feel free to submit a pull request or create an issue.

## License

This project is licensed under the MIT License. See the [LICENSE.md](./LICENSE.md) file for details.