# Contributing to the Waffle Framework

First off — thank you for considering a contribution to Waffle. Whether you're filing a bug, sharpening the docs, or shipping a feature, your help moves the ecosystem forward. Waffle is a community-driven, open-source project and contributions of every size are welcome.

## Code of Conduct

This project is governed by the [Waffle Code of Conduct](./CODE_OF_CONDUCT.md). By participating, you agree to uphold it. Please read it before contributing — we are committed to keeping this a welcoming, respectful community.

## How the project is organised (one minute)

Waffle is a **monorepo of independent Git submodules**. The umbrella repository — [`waffle-commons/monorepo`](https://github.com/waffle-commons/monorepo) — wires together 18 framework components plus the `skeleton`, `workspace`, `component-template`, and `documentation`. **Each component is its own Git repository, released independently on Packagist;** the umbrella is purely a development and integration convenience.

> **The one load-bearing rule:** every component depends **only** on `waffle-commons/contracts` (with the pure-function `waffle-commons/utils` as the single sanctioned shared foundation) — **never** on another component's concrete classes. This *Component-Agnosticism* invariant is enforced by `mago guard` on every pull request.

## 🗺️ Roadmap & RFCs come first

The project's **official roadmap and design record live in `project_system/`** inside the umbrella repository. It is the binding plan of record for the whole ecosystem:

- [`project_system/Roadmaps/`](https://github.com/waffle-commons/monorepo/tree/main/project_system/Roadmaps) — the official, release-by-release roadmap. **If a plan isn't written here, it isn't committed direction.**
- [`project_system/RFCs/`](https://github.com/waffle-commons/monorepo/tree/main/project_system/RFCs) — the authoritative design specifications (`RFC-001` … `RFC-022`) every component implements.
- [`project_system/Logs/Releases/`](https://github.com/waffle-commons/monorepo/tree/main/project_system/Logs/Releases) and [`Logs/Retrospectives/`](https://github.com/waffle-commons/monorepo/tree/main/project_system/Logs/Retrospectives) — what shipped in each wave, and what we learned.

**Before starting significant work:**

1. Check the current roadmap to see whether (and when) it's planned.
2. Read the relevant RFC and align your design with it.
3. For a new subsystem or a design change, open a discussion / propose the RFC + roadmap update **first**. Code that contradicts the roadmap or an RFC will be sent back.

Small, self-contained fixes (bugs, docs, tests) don't need an RFC — just open a PR. The full governance lifecycle (RFC → roadmap → release log → retrospective) is documented in [`docs/reference/project-system.md`](https://github.com/waffle-commons/monorepo/blob/main/docs/reference/project-system.md).

## Ways to contribute

### Reporting bugs

Search the [existing issues](https://github.com/waffle-commons/waffle/issues) first to avoid duplicates. A great report includes: a minimal reproducible example, the exact versions (Waffle component, PHP, OS), and the full text of any errors / stack traces / logs (set logging to `DEBUG` where useful).

### Suggesting enhancements

For anything beyond a small tweak, start a thread in [Discussions](https://github.com/waffle-commons/waffle/discussions) or check the roadmap above — significant features are coordinated through RFCs. Explain the problem (the *why*), then the proposed solution (the *what* / *how*), with concrete use cases and the alternatives you considered.

### Code & documentation

Patches, fixes, and features are very welcome. Look for [`good first issue`](https://github.com/waffle-commons/waffle/labels/good%20first%20issue) and [`help wanted`](https://github.com/waffle-commons/waffle/labels/help%20wanted). Every code change ships with tests, and every behaviour change ships with the matching Diátaxis doc update.

## Development workflow (monorepo + Docker)

All development runs inside the `waffle-dev` Docker container — **native PHP on the host is intentionally unsupported**, so everyone (and CI) runs the exact same toolchain.

**1. Clone the umbrella with submodules:**

```bash
git clone --recurse-submodules git@github.com:waffle-commons/monorepo.git waffle-commons
cd waffle-commons
```

**2. Start the dev environment (from `workspace/`):**

```bash
cd workspace
docker compose up -d        # builds + starts the `waffle-dev` container
```

**3. Work on any component, inside the container:**

```bash
docker exec -it -w /waffle-commons/waffle waffle-dev composer mago    # fmt + lint + analyze + guard
docker exec -it -w /waffle-commons/waffle waffle-dev composer tests   # PHPUnit (+ coverage)
```

**4. Fan a command across every component, or audit the whole ecosystem:**

```bash
./loop.sh composer mago     # run a command in every component
./coverage.sh               # aggregate PHPUnit coverage, enforce the 95% bar
./igor.sh                   # (a.k.a. `wfl igor`) worker-mode state-reset / memory-neutrality audit
```

The host-side `bin/wfl` CLI wraps the common Docker / mago / phpunit calls. Full walkthrough: [`docs/tutorials/setup-your-monorepo-workspace.md`](https://github.com/waffle-commons/monorepo/blob/main/docs/tutorials/setup-your-monorepo-workspace.md).

## Quality bar (every PR, every modified component)

- **Static analysis:** `composer mago` — `fmt` + `lint` + `analyze` + `guard` with **zero errors, zero warnings, zero baseline files** (the Zero-Baseline *Mago Purge Protocol*).
- **Tests:** `composer tests` — PHPUnit with **≥ 95% coverage** on changed code.
- **Worker safety:** `wfl igor` — **0 KO** (no state leaks across requests; FrankenPHP resident-worker safe).
- **Strict PHP 8.5:** `declare(strict_types=1)`, no `mixed`, typed constants, Property Hooks for DTO validation, asymmetric visibility (`public private(set)`), `#[\Override]` on every override.
- **Component-Agnosticism preserved** (`mago guard` green — only `contracts`/`utils` as dependencies).
- **Language:** code, identifiers, and framework-emitted logs/exceptions are **English**. The `skeleton`, `workspace`, and `academy` template apps are the only places French comments/strings are allowed.

## Pull requests

- **Target the specific component's repository**, not the umbrella, for code changes (e.g. a core change → a PR to [`waffle-commons/waffle`](https://github.com/waffle-commons/waffle)). Roadmap/RFC changes go to the umbrella's `project_system/`.
- Branch off `main` (or the active `pre-release/*` branch). Use **[Conventional Commits](https://www.conventionalcommits.org/)** (`feat:`, `fix:`, `docs:`, `chore:`, …).
- Keep each PR small and focused on a single logical change.
- `composer mago` and `composer tests` must pass in every modified component.
- Secure at least one `@waffle-commons/waffle-core` review approval (CODEOWNERS).
- Ship the matching docs: [`/documentation`](https://github.com/waffle-commons/monorepo/tree/main/documentation) for framework-facing changes, [`/docs`](https://github.com/waffle-commons/monorepo/tree/main/docs) for monorepo/process changes.

## Where to read more

- **Contributing to the monorepo (full guide):** [`/docs`](https://github.com/waffle-commons/monorepo/tree/main/docs) — tutorials, how-tos, reference, explanation.
- **Building an app on Waffle:** [`/documentation`](https://github.com/waffle-commons/monorepo/tree/main/documentation).
- **Project rules & AI-assistant conventions:** [`AGENTS.md`](https://github.com/waffle-commons/monorepo/blob/main/AGENTS.md) and [`CLAUDE.md`](https://github.com/waffle-commons/monorepo/blob/main/CLAUDE.md).

Thank you again for contributing — welcome aboard. 🧇
