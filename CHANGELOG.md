# Changelog — waffle-commons/waffle

All notable changes to this component are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Released in lockstep with the Waffle Commons umbrella tag.

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
