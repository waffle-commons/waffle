# **Contributing to the Waffle Framework**

First off, thank you sincerely for considering contributing to the Waffle Framework. Your involvement, 
whether big or small, is crucial for its growth and success. As an open-source project, Waffle thrives on 
the collective effort and expertise of the PHP community. We aim to build not just a great tool, but also 
a welcoming and collaborative environment around it.

This is a community-driven project, and we genuinely welcome contributions of all kinds. This includes, 
but is not limited to: reporting bugs, suggesting new features or improvements, writing or refining documentation, 
submitting code patches, creating examples, or even just participating in discussions to help others. 
Every contribution helps make Waffle better.


## **Code of Conduct**
To ensure a positive and inclusive environment for everyone, this project and everyone participating in it is 
governed by the [Waffle Code of Conduct](./CODE_OF_CONDUCT.md). By participating, you are expected to uphold 
this code. Please take a moment to read it before contributing. We are committed to enforcing this code to 
make our community a place where everyone feels safe, respected, and valued.

## **How Can I Contribute?**
There are many ways to contribute to the Waffle Framework's development. Here are some of the most common:

### **Reporting Bugs**
Encountering unexpected behavior or errors is a natural part of software development. If you find a bug in Waffle, 
your detailed report is invaluable.
- **Check Existing Issues First:** Before submitting a new bug report, please take a moment to search the 
[existing issues](https://github.com/waffle-commons/waffle/issues) to see if someone else has already reported 
the same problem. This avoids duplicates and allows you to add relevant information or context to an ongoing 
investigation.
- **Provide Clear Steps:** Use the [bug report template](https://github.com/waffle-commons/waffle/issues) 
when opening an issue. The most helpful bug reports include clear, concise, and step-by-step instructions on 
how to reliably reproduce the issue. A minimal reproducible example (the smallest amount of code necessary 
to demonstrate the bug) is often the key to a quick fix.
- **Include Environment Details:** Specify the exact versions you are using (Waffle framework, PHP, 
operating system, web server if applicable). Sometimes bugs are specific to certain environments.
- **Logs and Error Messages:** Include the full text of any relevant error messages, stack traces, or logs. 
Copying and pasting this information accurately is crucial. If possible, set the relevant logging level 
to DEBUG to capture more details.
- **Use the `bug` Label:** While maintainers will usually label issues, feel free to suggest the `bug` label 
if appropriate.

### **Suggesting Enhancements**
Do you have an idea that could make Waffle even better? We'd love to hear it! Enhancements can range from 
small quality-of-life improvements to entirely new features.
- **Discuss First (Optional but Recommended):** For significant changes or new features, consider starting 
a discussion in the [Discussions tab](https://github.com/waffle-commons/waffle/discussions) first. 
This allows the community and maintainers to provide feedback on the concept before you invest time in 
a detailed proposal or implementation.
- **Use the Template:** Open a [new feature request](https://github.com/waffle-commons/waffle/issues). 
Explain the problem your enhancement solves (the "why") and then describe your proposed solution 
(the "what" and "how").
- **Provide Context and Use Cases:** Why is this enhancement needed? Who would benefit from it? 
What specific scenarios does it enable or improve? Concrete examples are very helpful.
- **Consider Alternatives:** Briefly mention any alternative solutions you considered and why 
your proposed approach seems better.
- **Use `enhancement` or `feature` Labels:** Suggest the appropriate label for your idea.

### **Code Contributions**
Patches, bug fixes, and new features implemented through code are highly welcome.
- **Find an Issue:** A great way to start is by looking at issues labeled 
[`good first issue`](https://github.com/waffle-commons/waffle/labels/good%20first%20issue) (ideal for newcomers) 
or [`help wanted`](https://github.com/waffle-commons/waffle/labels/help%20wanted) (issues we'd appreciate community 
assistance with).
- **Discuss Your Approach:** Especially for larger changes, it's a good idea to comment on the relevant issue 
or open a new one to discuss your intended implementation strategy before you start coding. This ensures 
your work aligns with the project's direction and avoids duplicated effort.
- **Follow Coding Standards:** Waffle uses [Mago](https://mago.carthage.software/) for code formatting and 
static analysis. Please ensure your code adheres to the project's standards by running the appropriate 
commands inside the development environment (see workflow below). Pull requests that fail CI checks due 
to formatting or analysis errors will need correction.
- **Write Tests:** All code contributions must include relevant unit or integration tests using PHPUnit. 
Aim for high test coverage for any new or modified code. Contributions that decrease overall test coverage 
may not be accepted. See the "Running Tests" section below for how to execute the test suite.
- **Keep Pull Requests Focused:** Create small, focused pull requests that address a single issue or implement 
a single feature. This makes the review process much easier and faster. Avoid mixing unrelated changes in one PR.

## **Development Workflow using `waffle-commons/workspace`**
To ensure consistency between local development, CI, and potential contributions from others, 
Waffle development and integration testing is primarily managed through the dedicated 
`waffle-commons/workspace` project. This setup uses Docker and Composer path repositories to link the 
core framework and its components together in an isolated environment.

1. **Fork and Clone Required Repositories:**
- **Fork** the official `waffle-commons/waffle` repository on GitHub to your own account.
- **Clone** the official `waffle-commons/workspace` repository locally.
- **Clone** your **fork** of `waffle-commons/waffle` locally.
- _(Optional)_ Clone any other official `waffle-commons/*` component repositories you might need locally.

The core idea is to have the workspace project and your fork of waffle (and potentially other components) 
reside as sibling directories within a common parent folder. The recommended structure is `~/waffle-commons/`.
```
~/waffle-commons/          # Common parent directory
├── workspace/             # The official development environment orchestrator (Docker, Composer links)
│   ├── docker-compose.yml
│   ├── Dockerfile
│   ├── composer.json      # Defines links to ../waffle, ../http, etc.
│   └── ...
├── waffle/                # Your FORK of the core framework repository
│   ├── src/
│   ├── tests/
│   ├── composer.json
│   └── ...
└── http/                  # Example: Official Waffle HTTP component repository (clone if needed)
│   ├── src/
│   ├── composer.json
│   └── ...
└── ...                    # Other components as needed
```
Execute the following commands to set up this structure (replace `YOUR_USERNAME`):
```shell
# Create the parent directory if it doesn't exist
mkdir -p ~/waffle-commons
cd ~/waffle-commons

# Clone the workspace orchestrator (official repo)
git clone git@github.com:waffle-commons/workspace.git

# Clone YOUR FORK of the core Waffle framework
git clone git@github.com:YOUR_USERNAME/waffle.git

# Clone any other OFFICIAL components you need to modify or test alongside waffle
# git clone git@github.com:waffle-commons/http.git # For example
# git clone git@github.com:waffle-commons/yaml.git # For example
```
Cloning these repositories side-by-side allows the `workspace`'s Composer configuration to locate and link 
them using simple relative paths like `../waffle`.

2. **Configure Workspace Composer:**
The `composer.json` file located inside `~/waffle-commons/workspace/` is the central piece for linking 
your local development versions. It uses Composer's `path` repository type with the `options.versions` key 
to force the usage of your local clones.

Verify your `~/waffle-commons/workspace/composer.json` looks similar to this example, ensuring 
the `"url"` points correctly (`../waffle`) and the version in `"require"` matches the version specified 
under `"options.versions"`:
```json
{
    "name": "waffle-commons/workspace",
    // ...
    "require": {
        "php": "^8.4",
        "waffle-commons/waffle": "1.0.0-dev" // Must match version in options below
        // "waffle-commons/http": "1.0.0-dev" // Example for another component
    },
    "repositories": [
        {
            "type": "path",
            "url": "../waffle", // Relative path FROM workspace TO your waffle fork
            "options": {
                // Force Composer to treat the local path as this specific version
                "versions": { "waffle-commons/waffle": "0.1.0-dev" },
                "symlink": true // Create symlinks instead of copying
            }
        }
        // Add entries for other official waffle-commons/* components here if needed
        // ,{
        //    "type": "path",
        //    "url": "../http", // Relative path FROM workspace TO http
        //    "options": {
        //        "versions": { "waffle-commons/http": "1.0.0-dev" },
        //        "symlink": true
        //    }
        // }
    ],
    // ...
    "prefer-stable": false,   // Allow Composer to pick dev versions if needed
}
```

_(Adjust the development version string like `"1.0.0-dev"` consistently if the project uses a different 
convention for its development branches/versions.)_

3. **Start the Development Environment:**
The Docker setup is managed entirely within the `workspace` project. Navigate to its directory and use 
Docker Compose commands.
```shell
cd ~/waffle-commons/workspace

# Build the Docker image (only needed initially or if you modify the Dockerfile)
docker compose build

# Start the services (FrankenPHP web server) in detached mode (-d) (for the first time)
docker compose up -d

# Start the services (FrankenPHP web server)
docker compose start

# To view logs (useful for debugging startup issues):
# docker-compose logs -f waffle-dev
```
This typically starts a FrankenPHP server accessible via `http://localhost:8080` (or `https://localhost:8443`).

4. **Install Dependencies (Inside Docker):**
This is a crucial step. Run `composer install` _from within the running Docker container_, specifying 
the `/waffle-commons/workspace` directory. This command reads the `workspace/composer.json` and creates
symlinks inside the container's `/workspace/vendor` directory pointing to your local code 
(e.g., `/waffle-commons/waffle`, `/waffle-commons/http`).
```shell
# Execute 'composer install' inside the 'workspace' container,
# telling it to operate within the /waffle-commons/workspace directory.
docker exec waffle-dev -w=/waffle-commons/workspace composer install
```
After this, `/waffle-commons/workspace/vendor/waffle-commons/waffle` inside the container should be a symbolic 
link pointing to `/waffle-commons/waffle`. You can verify this using 
`docker exec waffle-dev ls -l /waffle-commons/workspace/vendor/waffle-commons/`.

_Note:_ Run `composer update` instead if `composer.lock` is out of sync or after adding new local path repositories.

5. **Create a New Branch:**
Before making changes, create a new branch in **your fork** of the `waffle-commons/waffle` repository:
```shell
cd ~/waffle-commons/waffle
git checkout -b feature/my-cool-feature # Or bugfix/fix-that-bug
```

6. **Making Changes:**
- Edit the code directly in your local `~/waffle-commons/waffle/` directory (your fork).
- Changes are instantly reflected inside the Docker container due to volume mounts and symlinks.

7. **Running Tests (Inside Docker):**
Run tests within the Docker container for consistency with CI.

- **Run Waffle's own unit/integration tests:** Execute PHPUnit using the configuration file within the mounted `waffle` directory:
```shell
docker exec waffle-dev -w=/waffle-commons/waffle composer tests
```
- Ensure all tests pass before committing.

8. **Static Analysis (Inside Docker):**
Run static analysis tools like `Mago` within the container, targeting the `waffle` codebase:
```shell
docker exec waffle-dev -w=/waffle-commons/waffle composer formatter
docker exec waffle-dev -w=/waffle-commons/waffle composer linter
docker exec waffle-dev -w=/waffle-commons/waffle composer analyzer
```
Fix any reported issues.

9. **Committing and Pull Requests:**
Your Git workflow operates on your local fork.

- Stage and commit your changes within `~/waffle-commons/waffle/`:
```shell
cd ~/waffle-commons/waffle
git add .\
git commit -m "feat: Add cool new feature" # Follow Conventional Commits
```
- Keep commits focused on single logical changes.
- Push your feature branch to **your fork** on GitHub:
```shell
git push origin feature/my-cool-feature
```
- From **your fork** on GitHub, open a Pull Request targeting the `main` branch (or appropriate development branch) 
of the **official `waffle-commons/waffle`** repository.
- Fill out the Pull Request template comprehensively, explaining your changes and linking relevant issues 
(e.g., `Fixes #123`). Ensure all CI checks pass on your PR.

**Thank you again for your contribution! Following this workflow helps keep development consistent and efficient 
for everyone involved.**

****
