# Contributing to the Waffle Framework

First off, thank you for considering contributing to Waffle. Your help is essential for making it a great tool for the PHP community.

This is a community-driven project, and we welcome contributions of all kinds, from bug reports and feature requests to code contributions and documentation improvements.

## Code of Conduct

This project and everyone participating in it is governed by a [Code of Conduct](./CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

If you encounter a bug, please help us by opening a [new bug report](https://github.com/waffle-commons/waffle/issues). Before submitting, please check if a similar issue already exists. Ensure you provide a clear title and a detailed description, including steps to reproduce the issue.

### Suggesting Enhancements

If you have an idea for a new feature or an improvement to an existing one, please open a [new feature request](https://github.com/waffle-commons/waffle/issues). We are always open to new ideas for making Waffle better.

### Code Contributions

If you'd like to contribute code, you can start by looking through our `help-wanted` or `good-first-issue` labels in the [issues tab](https://github.com/waffle-commons/waffle/issues).

## Development Workflow

1. **Fork the repository** on GitHub.

2. **Clone your fork** locally:

```shell
git clone git@github.com:YOUR_USERNAME/waffle.git
```

3. **Navigate to the project directory:**

```shell 
cd waffle
```


4. **Create a new branch** for your changes. Please use a descriptive name:

```shell
# For a new feature
git checkout -b feature/my-new-feature
# For a bug fix
git checkout -b bugfix/fix-for-that-bug
# Create a local environment file
touch .env.local
```

5. **Fill** your `.env.local` file:
```dotenv
# App
APP_ENV=dev
APP_DEBUG=true
```


6. **Install dependencies** using Composer:

```shell
composer install
```


7. **Make your changes** to the code.

8. **Run static analysis** to ensure your code meets our quality standards:

```shell
composer mago
```


9.**Run the test suite** to ensure everything is still working correctly:

```shell
composer tests
```


10. **Commit your changes** with a clear and concise commit message following conventional commit standards.

11. **Push your branch** to your fork:

```shell
git push origin feature/my-new-feature
```


12. **Open a Pull Request** against the `main` branch of the `waffle-commons/waffle` repository. Please fill out the PR template to help us understand your changes.

Thank you again for your contribution!