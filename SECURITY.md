# Security Policy

This policy covers every Waffle package: the `waffle-commons` repositories released together on Packagist, this one included.

## Reporting a vulnerability

**Please do not report a vulnerability in a public issue, a pull request or a discussion.** Report it privately, through either channel:

- **GitHub private vulnerability reporting:** open this repository's **Security** tab, then **Report a vulnerability**.
- **Email:** `security@eightyfour.ovh`, the project's private security mailing list.

Please include what you can of the following:

- the package and the version affected;
- the steps to reproduce;
- a proof of concept, an example of exploitation, or a patch;
- the impact you see (for example remote code execution or information disclosure).

## What happens next

- We acknowledge your report within 48 hours.
- We triage it, reproduce it, fix it and prepare its release, and we keep you informed along the way.
- Once the fix is released, we agree the public disclosure with you, and we credit you if you wish.

## Supported versions

| Version | Security fixes |
|---|---|
| `0.1.0-beta*` | The latest beta release only. A fix ships in the next release; earlier betas are not patched. |
| `1.x` | From `1.0.0`: the latest `1.x` minor release. |

When a new major version is released, the previous major keeps receiving fixes for critical vulnerabilities for six more months.

## Automated checks

Every pull request and every push to `main` runs, among other checks:

- `composer audit`, for known vulnerabilities in third-party dependencies;
- Psalm's taint analysis, for unsafe data flows in the code;
- TruffleHog, for verified secrets in the git history;
- igor-php, for state that would leak between requests in FrankenPHP's worker mode.
