<?php

declare(strict_types=1);

namespace WaffleTests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Contracts\Container\CompiledContainerInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Factory\CompiledContainerLoader;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\MockContainer;
use WaffleTests\Helper\RecordingLogger;

/**
 * AOT-01 kernel fast-path loader tests (RFC-019 mandatory fallback).
 *
 * Verifies: with WAFFLE_AOT=1 + a valid artifact, the compiled container wraps the
 * runtime and serves the same services; with the flag off, a missing artifact, a
 * corrupt artifact, a wrong class, or a construction failure, the loader returns
 * the runtime container unchanged and (when enabled) logs a warning.
 */
#[CoversClass(CompiledContainerLoader::class)]
final class CompiledContainerLoaderTest extends TestCase
{
    private ?string $previousFlag = null;
    private string $tempDir = '';

    /** @var list<string> */
    private array $artifacts = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $flag = getenv(CompiledContainerLoader::ENV_FLAG);
        $this->previousFlag = $flag === false ? null : $flag;
        $this->tempDir = sys_get_temp_dir() . '/waffle_aot_' . uniqid('', true);
        mkdir($this->tempDir, 0o777, true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->previousFlag === null) {
            putenv(CompiledContainerLoader::ENV_FLAG);
        } else {
            putenv(CompiledContainerLoader::ENV_FLAG . '=' . $this->previousFlag);
        }

        foreach ($this->artifacts as $file) {
            if (!is_file($file)) {
                continue;
            }

            @unlink($file);
        }
        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    private function enable(): void
    {
        putenv(CompiledContainerLoader::ENV_FLAG . '=1');
    }

    private function disable(): void
    {
        putenv(CompiledContainerLoader::ENV_FLAG);
    }

    /**
     * Writes an artifact defining a CompiledContainer that composes the runtime
     * container and delegates get()/has()/reset() to it. Returns the unique FQCN.
     */
    private function writeValidArtifact(): string
    {
        $class = 'CompiledContainerFixture' . str_replace('.', '', uniqid('', true));
        $fqcn = 'WaffleTests\\Factory\\Generated\\' . $class;
        $file = $this->tempDir . '/' . $class . '.php';

        $source = <<<PHP
            <?php

            declare(strict_types=1);

            namespace WaffleTests\\Factory\\Generated;

            final class {$class} implements \\Waffle\\Commons\\Contracts\\Container\\CompiledContainerInterface
            {
                public function __construct(
                    private readonly \\Waffle\\Commons\\Contracts\\Container\\ContainerInterface \$runtime,
                ) {}

                #[\\Override]
                public function get(string \$id): mixed
                {
                    return \$this->runtime->get(\$id);
                }

                #[\\Override]
                public function has(string \$id): bool
                {
                    return \$this->runtime->has(\$id);
                }

                #[\\Override]
                public function set(string \$id, object|callable|string \$concrete): void
                {
                    \$this->runtime->set(\$id, \$concrete);
                }

                #[\\Override]
                public function reset(): void
                {
                    \$this->runtime->reset();
                }
            }
            PHP;

        file_put_contents($file, $source);
        $this->artifacts[] = $file;

        return $fqcn;
    }

    public function testDisabledFlagReturnsRuntimeUnchanged(): void
    {
        $this->disable();
        $runtime = new MockContainer();
        $logger = new RecordingLogger();

        $loader = new CompiledContainerLoader(artifactPath: $this->tempDir . '/missing.php', logger: $logger);

        static::assertSame($runtime, $loader->load($runtime));
        static::assertSame([], $logger->records, 'no warning when AOT is not opted into');
    }

    public function testEnabledWithValidArtifactServesSameServices(): void
    {
        $this->enable();
        $runtime = new MockContainer();
        $marker = new \stdClass();
        $runtime->set('service.x', $marker);

        $fqcn = $this->writeValidArtifact();
        $file = end($this->artifacts);
        static::assertIsString($file);

        $loader = new CompiledContainerLoader(artifactPath: $file, compiledClass: $fqcn, logger: new RecordingLogger());

        $result = $loader->load($runtime);

        static::assertInstanceOf(CompiledContainerInterface::class, $result);
        static::assertNotSame($runtime, $result, 'a valid artifact swaps in the compiled container');
        // Same service graph: the compiled container resolves through the runtime.
        static::assertSame($marker, $result->get('service.x'));
        static::assertTrue($result->has('service.x'));
    }

    public function testSuccessfulLoadWarnsTheOperatorToRegenerateTheArtifact(): void
    {
        // AOT-04: a successful load must emit a prominent staleness warning so a
        // stale artifact cannot silently serve an outdated graph.
        $this->enable();
        $runtime = new MockContainer();
        $logger = new RecordingLogger();

        $fqcn = $this->writeValidArtifact();
        $file = end($this->artifacts);
        static::assertIsString($file);

        $loader = new CompiledContainerLoader(artifactPath: $file, compiledClass: $fqcn, logger: $logger);

        $result = $loader->load($runtime);

        static::assertInstanceOf(CompiledContainerInterface::class, $result);
        static::assertCount(1, $logger->records, 'exactly one warning on a clean load');
        static::assertSame('warning', $logger->records[0]['level']);
        static::assertStringContainsString('AOT compiled container loaded', $logger->records[0]['message']);
        static::assertStringContainsString('MUST regenerate', $logger->records[0]['message']);
        static::assertStringContainsString(CompiledContainerLoader::REBUILD_COMMAND, $logger->records[0]['message']);
        static::assertSame(
            CompiledContainerLoader::REBUILD_COMMAND,
            $logger->records[0]['context']['rebuild_command'] ?? null,
        );
    }

    public function testEnabledWithMissingArtifactFallsBackAndWarns(): void
    {
        $this->enable();
        $runtime = new MockContainer();
        $logger = new RecordingLogger();

        $loader = new CompiledContainerLoader(artifactPath: $this->tempDir . '/does-not-exist.php', logger: $logger);

        static::assertSame($runtime, $loader->load($runtime));
        static::assertNotEmpty($logger->records);
        static::assertStringContainsString('no compiled-container artifact', $logger->records[0]['message']);
    }

    public function testEnabledWithCorruptArtifactFallsBackAndWarns(): void
    {
        $this->enable();
        $runtime = new MockContainer();
        $logger = new RecordingLogger();

        // Artifact exists but defines no class (its class never materialises).
        $file = $this->tempDir . '/corrupt.php';
        file_put_contents($file, "<?php\n\ndeclare(strict_types=1);\n\n// no class here\n");
        $this->artifacts[] = $file;

        $loader = new CompiledContainerLoader(
            artifactPath: $file,
            compiledClass: 'WaffleTests\\Factory\\Generated\\NeverDefined',
            logger: $logger,
        );

        static::assertSame($runtime, $loader->load($runtime));
        static::assertNotEmpty($logger->records);
        static::assertStringContainsString('did not define the expected class', $logger->records[0]['message']);
    }

    public function testEnabledWithWrongClassTypeFallsBackAndWarns(): void
    {
        $this->enable();
        $runtime = new MockContainer();
        $logger = new RecordingLogger();

        // Artifact defines a class that does NOT implement CompiledContainerInterface.
        $class = 'NotACompiledContainer' . str_replace('.', '', uniqid('', true));
        $fqcn = 'WaffleTests\\Factory\\Generated\\' . $class;
        $file = $this->tempDir . '/' . $class . '.php';
        file_put_contents(
            $file,
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace WaffleTests\\Factory\\Generated;\n\nfinal class {$class}\n{\n    public function __construct(mixed \$runtime) {}\n}\n",
        );
        $this->artifacts[] = $file;

        $loader = new CompiledContainerLoader(artifactPath: $file, compiledClass: $fqcn, logger: $logger);

        static::assertSame($runtime, $loader->load($runtime));
        static::assertNotEmpty($logger->records);
        static::assertStringContainsString(
            'does not implement CompiledContainerInterface',
            $logger->records[0]['message'],
        );
    }

    public function testEnabledWithConstructionFailureFallsBackAndWarns(): void
    {
        $this->enable();
        $runtime = new MockContainer();
        $logger = new RecordingLogger();

        // Artifact's constructor throws — the loader must catch and fall back.
        $class = 'ThrowingContainer' . str_replace('.', '', uniqid('', true));
        $fqcn = 'WaffleTests\\Factory\\Generated\\' . $class;
        $file = $this->tempDir . '/' . $class . '.php';
        $source = <<<PHP
            <?php

            declare(strict_types=1);

            namespace WaffleTests\\Factory\\Generated;

            final class {$class} implements \\Waffle\\Commons\\Contracts\\Container\\CompiledContainerInterface
            {
                public function __construct(\\Waffle\\Commons\\Contracts\\Container\\ContainerInterface \$runtime)
                {
                    throw new \\RuntimeException('boom');
                }

                #[\\Override]
                public function get(string \$id): mixed { return null; }

                #[\\Override]
                public function has(string \$id): bool { return false; }

                #[\\Override]
                public function set(string \$id, object|callable|string \$concrete): void {}

                #[\\Override]
                public function reset(): void {}
            }
            PHP;
        file_put_contents($file, $source);
        $this->artifacts[] = $file;

        $loader = new CompiledContainerLoader(artifactPath: $file, compiledClass: $fqcn, logger: $logger);

        static::assertSame($runtime, $loader->load($runtime));
        static::assertNotEmpty($logger->records);
        static::assertStringContainsString('Failed to load the compiled container', $logger->records[0]['message']);
    }

    public function testRuntimeContainerTypeIsContractInterface(): void
    {
        // Guards the perimeter intent: the loader only ever requires the contract.
        $this->disable();
        $runtime = new MockContainer();
        $loader = new CompiledContainerLoader(artifactPath: $this->tempDir . '/x.php');

        $result = $loader->load($runtime);
        static::assertInstanceOf(ContainerInterface::class, $result);
    }
}
