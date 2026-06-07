[![Discord](https://img.shields.io/discord/755288001592033391?logo=discord)](https://discord.gg/eKgywnfXr2)
[![PHP Version Require](http://poser.pugx.org/waffle-commons/waffle/require/php)](https://packagist.org/packages/waffle-commons/waffle)
[![PHP CI](https://github.com/waffle-commons/waffle/actions/workflows/main.yml/badge.svg)](https://github.com/waffle-commons/waffle/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/waffle-commons/waffle/graph/badge.svg?token=d74ac62a-7872-4035-8b8b-bcc3af1991e0)](https://codecov.io/gh/waffle-commons/waffle)
[![Latest Stable Version](http://poser.pugx.org/waffle-commons/waffle/v)](https://packagist.org/packages/waffle-commons/waffle)
[![Latest Unstable Version](http://poser.pugx.org/waffle-commons/waffle/v/unstable)](https://packagist.org/packages/waffle-commons/waffle)
[![Total Downloads](https://img.shields.io/packagist/dt/waffle-commons/waffle.svg)](https://packagist.org/packages/waffle-commons/waffle)
[![Packagist License](https://img.shields.io/packagist/l/waffle-commons/waffle)](https://github.com/waffle-commons/waffle/blob/main/LICENSE.md)

Waffle — the Kernel
===================

> **Release:** `0.1.0-beta3` &nbsp;|&nbsp; [`CHANGELOG.md`](./CHANGELOG.md)

The application kernel. Orchestrates request handling against the PSR-15 middleware stack, dispatches `RequestReceivedEvent` / `ResponseGeneratedEvent` / `TerminateEvent`, and resolves controllers via the container. The kernel itself stays agnostic of routing, security, logging, and HTTP — every concrete dependency is injected.

## 🆕 Beta-1 highlights

- **Kernel decoupling.** `AbstractKernel::handle()` resolves the terminal handler from the container under `Psr\Http\Server\RequestHandlerInterface` — there is no hard-coded `new ControllerDispatcher(...)` on the hot path. `configure()` registers a default `ControllerDispatcher` only when the slot is empty (`has()`-gated, idempotent), so an app can swap in its own terminal handler by pre-registering one.
- **Native DTO validation → 422.** `ControllerArgumentResolver` hydrates `#[Dto]`-tagged parameters from the parsed request body, letting PHP 8.5 *set* property hooks run their assertions during construction. A hook failure is trapped and re-thrown as a unified `ValidationException`, which the error handler renders as RFC 7807 `422 Unprocessable Entity` — closing the Mass-Assignment gap without any external validation package.

## 📦 Installation

```bash
composer require waffle-commons/waffle
```

## 🧱 Surface

| Class | Role |
| :--- | :--- |
| `Waffle\Kernel` | Concrete kernel. Extends `AbstractKernel`. |
| `Waffle\Abstract\AbstractKernel` | Implements `KernelInterface::boot/configure/handle/reset`; provides DI setters for `setContainerImplementation`, `setConfiguration`, `setSecurity`, `setMiddlewareStack`, `setEventDispatcher`. |
| `Waffle\Abstract\AbstractController` | Convenient base for app controllers; provides PSR-7 helper accessors. |
| `Waffle\Abstract\AbstractSystem` | Internal lifecycle binder used by `Waffle\Core\System`. |
| `Waffle\Core\System` | Boots the security / container linkage once the kernel reaches `configure()`. |
| `Waffle\Core\BaseController` | Default `BaseControllerInterface` implementation. |
| `Waffle\Handler\ControllerDispatcher` | Terminal PSR-15 handler that the middleware stack falls through to. Calls the controller method resolved from `_controller` and `_route_params`. |
| `Waffle\Handler\ControllerArgumentResolver` | Hydrates controller parameters, including `#[Dto]`-tagged DTOs from the parsed body. |
| `Waffle\Handler\ControllerResponseConverter` | Converts a controller's scalar / array return into a PSR-7 `ResponseInterface`. |
| `Waffle\Exception\*` | Domain exceptions (`WaffleException`, `RouteNotFoundException`, `RenderingException`, `ValidationException`, `InvalidConfigurationException`). |

## 🚀 Composing a kernel

```php
use Waffle\Kernel;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Config\Config;
use Waffle\Commons\Pipeline\MiddlewareStack;
use Waffle\Commons\Security\Security;
use Waffle\Commons\EventDispatcher\Dispatcher\EventDispatcher;
use Waffle\Commons\EventDispatcher\Provider\ListenerProvider;
use Waffle\Commons\Log\StreamLogger;

$config = new Config(__DIR__ . '/config', getenv('APP_ENV') ?: 'prod');

$container = new Container();
$container->set(ConfigInterface::class, $config);

$kernel = new Kernel(new StreamLogger());
$kernel->setConfiguration($config);
$kernel->setContainerImplementation($container);
$kernel->setSecurity(new Security($config));
$kernel->setMiddlewareStack((new MiddlewareStack())
    ->add(new ErrorHandlerMiddleware($renderer, $logger))
    ->add(new TrustedHostMiddleware($config->getArray('waffle.trusted_hosts', []) ?? []))
    ->add(new CoreRoutingMiddleware($router))
    ->add(new SecurityMiddleware($kernel->security))
);
$kernel->setEventDispatcher(new EventDispatcher(new ListenerProvider()));
```

The setter signatures, verbatim from `src/Abstract/AbstractKernel.php`:

```php
public function setContainerImplementation(PsrContainerInterface $container): void;
public function setConfiguration(ConfigInterface $config): void;
public function setSecurity(SecurityInterface $security): void;
public function setMiddlewareStack(MiddlewareStackInterface $stack): void;
public function setEventDispatcher(EventDispatcherInterface $dispatcher): void;
```

The constructor takes a PSR-3 logger only (defaults to `NullLogger`):

```php
public function __construct(protected LoggerInterface $logger = new NullLogger())
```

## 🔁 Request lifecycle

1. `handle(ServerRequestInterface)` ensures `boot()` + `configure()` have run (idempotent — the `$booted` guard short-circuits subsequent calls).
2. `validateState()` rejects an unconfigured kernel with `RuntimeException` / `ContainerException` / `NotFoundException` as appropriate.
3. `RequestReceivedEvent` is dispatched; listeners may swap the request.
4. The middleware stack runs; the terminal handler is a `ControllerDispatcher` resolving the route's `_controller` + `_route_params`.
5. `ResponseGeneratedEvent` is dispatched; listeners may swap the response.
6. The response is returned to `WaffleRuntime` which emits it and calls `terminate()` for post-emission listeners.
7. `reset()` clears request-scoped state (container reset).

## 📨 Built-in events

- `Waffle\Event\RequestReceivedEvent` — fires before the middleware pipeline runs.
- `Waffle\Event\ResponseGeneratedEvent` — fires after the pipeline returns.
- `Waffle\Event\TerminateEvent` — fires after the response is emitted (for heavy async work).

All three are PSR-14 events; `RequestReceivedEvent` and `ResponseGeneratedEvent` are *not* stoppable — they expose mutator methods (`getRequest()`, `getResponse()`) so listeners can replace the message.

## 🐘 PHP 8.5 features used

- `protected(set)` asymmetric visibility on `$system` and `$middlewareStack`.
- Constructor property promotion on `$logger`.
- Typed-constant defaults (`Constant::ENV_PROD`).
- `#[\Override]` on every method overriding `KernelInterface`.

## 🧭 Architectural boundary (`mago guard`)

An active dependency **perimeter** is enforced on every CI run by `vendor/bin/mago guard` (bundled into `composer mago`; zero baselines). The rules live in [`mago.toml`](./mago.toml) under `[guard.perimeter]` — a forbidden `use` statement fails the build, not a reviewer.

As the framework assembly package, `waffle` lives under the top-level `Waffle` namespace (not `Waffle\Commons\*`). Production code under `Waffle` may depend **only** on:

- `Waffle\**` — itself (the kernel, handlers, events, and factories)
- `Waffle\Commons\Contracts\**` — the shared contracts package
- `Waffle\Commons\Utils\**` — the `ClassParser` discovery helper
- `Psr\**` — PSR interfaces (PSR-7 / PSR-11 / PSR-15 / PSR-17)
- `@global` + `Psl\**` — PHP core and the PHP Standard Library

Test code under `WaffleTests` is unrestricted (`@all`). Structural rules are guarded too: interfaces must be named `*Interface`, `Exception\**` classes must end in `*Exception`, and any `Enum\**` namespace may hold only `enum` declarations.

Note: `waffle` depends only on `contracts` + `utils` directly. The concrete components (`http`, `routing`, `security`, …) are wired at the **application** layer (e.g. the skeleton's `AppKernelFactory`), not pulled in here — that is what keeps the kernel component-agnostic.

## 🧪 Testing

```bash
docker exec -w /waffle-commons/waffle waffle-dev composer tests
```

## 📄 License

MIT — see [LICENSE.md](./LICENSE.md).
