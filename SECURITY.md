# Security Policy

## Introduction

The security of the Waffle framework is a top priority. We are committed to ensuring our framework is a secure foundation for building modern PHP applications. Our development philosophy is "Security by Design," meaning that security considerations are integrated into every stage of the development lifecycle, from initial architecture to the CI/CD pipeline. We appreciate the efforts of security researchers and the community in helping us maintain a high standard of security.

## Reporting a Vulnerability

If you discover a security vulnerability within the Waffle framework, please help us by reporting it responsibly.

**Please do not disclose security vulnerabilities publicly or in public GitHub issues.** This ensures that we have the opportunity to investigate and address the issue before it becomes public knowledge, protecting all users of the framework.

Instead, please send a detailed report to our private security mailing list: `security@eightyfour.ovh`.

### What to Include in a Report

To help us assess and triage the vulnerability effectively, please include as much of the following information as possible:

 - A clear and concise description of the vulnerability.

 - The version(s) of the framework affected.

 - Detailed steps to reproduce the vulnerability.

 - If possible, a proof-of-concept, an example of exploitation, or a patch.

 - The potential impact of the vulnerability (e.g., Remote Code Execution, Information Disclosure, etc.).

### Our Commitment

 - We are committed to working with the security community to resolve verified vulnerabilities.

 - We will acknowledge your email within 48 hours.

 - We will work with you to understand, validate, and resolve the issue as quickly as possible. Our process typically involves triaging the report, reproducing the issue, developing a patch, and preparing for disclosure.

 - We will keep you informed of our progress throughout the process.

 - Once the vulnerability is fixed, we will coordinate with you on the public disclosure. We are happy to provide credit to researchers who report vulnerabilities responsibly.

## Supported Versions

Security is an ongoing process. To ensure you are protected, it is essential to use a supported version of the Waffle framework. Security updates are provided for the following versions:


| Version | Supported          |
|---------| ------------------ |
| 1.0     | :white_check_mark: |

When a new major version (e.g., 2.0) is released, the previous major version (1.x) will continue to receive **critical security fixes for an additional 6 months** to allow for a smooth transition.

## Integrated Security Tooling

The Waffle framework is developed with a proactive security approach. Our Continuous Integration (CI) pipeline on GitHub Actions includes several automated security checks on every pull request and push to the `main` branch:

 - **Composer Audit:** We use `composer audit` to continuously check for known vulnerabilities in our third-party dependencies (Software Composition Analysis - SCA).

 - **Psalm Taint Analysis:** We use Psalm's taint analysis features for Static Application Security Testing (SAST). This helps us identify potential security issues like SQL injection, XSS, or unsafe user input handling directly within the framework's codebase.

We believe that this automated approach helps us catch and fix potential vulnerabilities before they can impact users.