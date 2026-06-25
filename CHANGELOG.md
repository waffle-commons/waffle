# Changelog — waffle-commons/waffle

All notable changes to this component are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Released in lockstep with the Waffle Commons umbrella tag.

## [0.1.0-beta5] — 2026-06-26

**Theme: AOT fast-path, reactive flush & immutable kernel.**

### Added
- `Factory\CompiledContainerLoader` — AOT-01 kernel fast path. When `WAFFLE_AOT=1` and a compiled artifact (`var/cache/CompiledContainer.php`) exists and loads cleanly, `Abstract\AbstractKernel::boot()` swaps the locked runtime container for the reflection-free `Contracts\Container\CompiledContainerInterface` wrapper; on any miss (disabled, missing/corrupt artifact, wrong class, construction failure) it logs a PSR-3 `warning` and returns the runtime container **unchanged** (RFC-019 mandatory fallback). A successful load emits a prominent staleness warning since the artifact is not fingerprinted against current code (AOT-04).
- `Event\Listener\BroadcastFlushListener` — on `Event\TerminateEvent`, drains the request-scoped `Reactive\RequestBroadcastBuffer` and publishes each recorded mutation through a `Contracts\Reactive\BroadcastTransportInterface` after the response is emitted (REACTIVE-01 / AXE3).
- `Reactive\RequestBroadcastBuffer` — request-scoped, in-memory accumulator of `#[Broadcast]` write-hook mutations with no I/O on the hot path; implements `Contracts\Service\ResettableInterface` **directly** so the kernel empties it after every request.
- `Reactive\Sse\SseBroadcastTransport` — Server-Sent Events transport that serializes each `MutationRecord` into the SSE wire format and writes to an injected sink, sanitizing the channel against CR/LF/NUL SSE field-injection.
- `Event\Listener\DeferredTaskFlushListener` — on `Event\TerminateEvent`, drains the deferred-task queue via `Contracts\Async\TaskRunnerInterface` so short post-response work runs out of the user-perceived latency path; wraps the whole drain in a catch-all so a teardown failure can never break `terminate()` (ASYNC-01).

### Changed
- `Abstract\AbstractKernel` now requires every collaborator (config, container, security, middleware stack, logger) as a **mandatory constructor parameter**, so a half-built kernel is unrepresentable. The nullable fields, `set*()` setters, and `validateState()` temporal-coupling machinery are removed; only the lifecycle event dispatcher remains an optional boot-time `#[WorkerSafe]` setter (ARCH-03). `Kernel` mirrors the new constructor signature.
- `Handler\ControllerResponseConverter` opens a `waffle.response.convert` internal telemetry span (default `NullTracer`, no-op unless an OTel bridge is wired) and records the resolved `http.response.status_code` (OBS-01).
- `Abstract\AbstractKernel::logAndThrow()` is now typed `: never` — the always-throwing helper no longer claims a `void` return (MODERN-02).

## [0.1.0-beta4] — 2026-06-13

**Theme: kernel lifecycle extensibility & diagnostics.**

### Added
- Typed kernel lifecycle events dispatched by `Abstract\AbstractKernel` — `Event\RequestReceivedEvent`, `Event\ResponseGeneratedEvent`, `Event\TerminateEvent` (ARCH-04).
- `Event\Listener\OrphanedConnectionListener` — on `TerminateEvent`, emits a PSR-3 `warning` for a pooled PDO connection left open at request end (DIAG-03).

### Changed
- `Handler\ControllerDispatcher` resolves response factories via an explicit `instanceof ResponseFactoryAwareInterface` check rather than method-existence heuristics (ARCH-05).
- Worker-safety migration to igor-php 0.7 (`#[WorkerSafe]`).

## [0.1.0-beta3] — 2026-06-07

**Theme: identity federation & stateless persistence (ecosystem wave).**

### Changed
- `AbstractKernel` drains resettable loggers on kernel reset and implements the new `Contracts\Core\TerminableInterface` (post-response teardown hook for the FrankenPHP worker loop).
- Dependency-injection behaviour documented inline in `AbstractController` / `AbstractKernel` (no behavioural change).
- Lockstep version bump; `composer.lock` refreshed with the beta-3 dependency wave.

## [0.1.0-beta2.1] — 2026-05-30

### Changed
- Lockstep re-tag of `0.1.0-beta2` (umbrella housekeeping patch) — no source changes in this component.

## [0.1.0-beta2] — 2026-05-29

### Changed
- Lockstep version bump only. No behavioural changes since `0.1.0-beta1`.
- `composer.lock` refreshed to align with the ecosystem-wide dependency wave.

## [0.1.0-beta1]

See the umbrella [CHANGELOG](../CHANGELOG.md#010-beta1) for the full Beta-1 narrative — `AbstractKernel::handle()` resolves the terminal handler from the container under `RequestHandlerInterface` (decoupling); native DTO validation pipeline (`#[Dto]` + Property Hooks → `ValidationException` → RFC 7807 `422`).
