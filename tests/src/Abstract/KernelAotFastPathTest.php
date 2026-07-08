<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Waffle\Abstract\AbstractKernel;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\CompiledContainerInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Factory\CompiledContainerLoader;
use Waffle\Kernel;
use WaffleTests\Abstract\Helper\FakeMiddlewareStack;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\MockContainer;
use WaffleTests\Helper\RecordingLogger;

/**
 * Covers the AOT-01 fast-path wiring inside {@see AbstractKernel::configure()}:
 * with WAFFLE_AOT=1 + a valid artifact at APP_ROOT/var/cache the kernel swaps in
 * the compiled container (serving the same services); with the artifact missing it
 * logs a warning and keeps the reflection container.
 */
#[CoversClass(AbstractKernel::class)]
#[AllowMockObjectsWithoutExpectations]
final class KernelAotFastPathTest extends TestCase
{
    private ?string $previousFlag = null;
    private string $artifactPath = '';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $flag = getenv(CompiledContainerLoader::ENV_FLAG);
        $this->previousFlag = $flag === false ? null : $flag;
        $this->artifactPath = APP_ROOT . '/' . CompiledContainerLoader::DEFAULT_ARTIFACT;
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->previousFlag === null) {
            putenv(CompiledContainerLoader::ENV_FLAG);
        } else {
            putenv(CompiledContainerLoader::ENV_FLAG . '=' . $this->previousFlag);
        }

        if (is_file($this->artifactPath)) {
            @unlink($this->artifactPath);
        }
        parent::tearDown();
    }

    private function makeConfig(): ConfigInterface
    {
        /** @var ConfigInterface&MockObject $config */
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getString')->willReturn(null);

        return $config;
    }

    private function makeKernel(ContainerInterface $container, RecordingLogger $logger): Kernel
    {
        /** @var SecurityInterface&MockObject $security */
        $security = $this->createMock(SecurityInterface::class);

        return new class($this->makeConfig(), $container, $security, new FakeMiddlewareStack(), $logger) extends
            Kernel {
            public function exposeContainer(): ContainerInterface
            {
                return $this->container;
            }
        };
    }

    private function writeValidArtifact(): void
    {
        $dir = dirname($this->artifactPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        // The generated class composes the runtime container by the contract type,
        // so the MockContainer used in tests satisfies its constructor.
        $source = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Waffle\\Generated;

            if (!\\class_exists(\\Waffle\\Generated\\CompiledContainer::class, false)) {
                final class CompiledContainer implements \\Waffle\\Commons\\Contracts\\Container\\CompiledContainerInterface
                {
                    public function __construct(
                        private readonly \\Waffle\\Commons\\Contracts\\Container\\ContainerInterface \$runtime,
                    ) {}

                    #[\\Override]
                    public function get(string \$id): mixed { return \$this->runtime->get(\$id); }

                    #[\\Override]
                    public function has(string \$id): bool { return \$this->runtime->has(\$id); }

                    #[\\Override]
                    public function set(string \$id, object|callable|string \$concrete): void { \$this->runtime->set(\$id, \$concrete); }

                    #[\\Override]
                    public function reset(): void { \$this->runtime->reset(); }
                }
            }
            PHP;
        file_put_contents($this->artifactPath, $source);
    }

    public function testAotEnabledWithArtifactSwapsInCompiledContainer(): void
    {
        putenv(CompiledContainerLoader::ENV_FLAG . '=1');
        $this->writeValidArtifact();

        $runtime = new MockContainer();
        $marker = new \stdClass();
        $runtime->set('service.marker', $marker);

        $logger = new RecordingLogger();
        $kernel = $this->makeKernel($runtime, $logger);
        $kernel->configure();

        $container = $kernel->exposeContainer();
        static::assertInstanceOf(CompiledContainerInterface::class, $container);
        // Same service graph through the compiled container.
        static::assertSame($marker, $container->get('service.marker'));
    }

    public function testAotEnabledWithoutArtifactFallsBackToReflection(): void
    {
        putenv(CompiledContainerLoader::ENV_FLAG . '=1');
        if (is_file($this->artifactPath)) {
            unlink($this->artifactPath);
        }

        $runtime = new MockContainer();
        $logger = new RecordingLogger();
        $kernel = $this->makeKernel($runtime, $logger);
        $kernel->configure();

        $container = $kernel->exposeContainer();
        // No artifact → the kernel keeps the reflection (runtime) container.
        static::assertNotInstanceOf(CompiledContainerInterface::class, $container);
        static::assertSame($runtime, $container);
        static::assertNotEmpty($logger->records, 'a missing artifact must log a warning');
    }

    public function testDefaultPathKeepsReflectionContainer(): void
    {
        // No WAFFLE_AOT env at all → AOT is off, behaviour unchanged.
        putenv(CompiledContainerLoader::ENV_FLAG);

        $runtime = new MockContainer();
        $logger = new RecordingLogger();
        $kernel = $this->makeKernel($runtime, $logger);
        $kernel->configure();

        static::assertSame($runtime, $kernel->exposeContainer());
        static::assertSame([], $logger->records, 'no warning on the default (non-AOT) path');
    }

    /**
     * AOT-07: a Resettable service must be reset AT MOST once per request, even
     * when it is reachable under two ids (e.g. a passthrough service that is also
     * an inlined service's dependency) AND the compiled container sits in the
     * reset path. The compiled container delegates reset() to the runtime, which
     * de-duplicates by object identity — so a single object reachable twice resets
     * exactly once.
     */
    public function testResettableServiceReachableTwiceIsResetOnlyOnceThroughAot(): void
    {
        putenv(CompiledContainerLoader::ENV_FLAG . '=1');
        $this->writeValidArtifact();

        $service = new class implements \Waffle\Commons\Contracts\Service\ResettableInterface {
            public int $resets = 0;

            #[\Override]
            public function reset(): void
            {
                ++$this->resets;
            }
        };

        // A runtime container whose reset() cascades to its Resettable instances,
        // de-duplicating by object identity (the real container's contract).
        $runtime = new class($service) extends MockContainer {
            /** @param object $service */
            public function __construct(object $service)
            {
                // Same object reachable under two distinct ids.
                $this->set('service.passthrough', $service);
                $this->set('service.inlined_dependency', $service);
            }

            #[\Override]
            public function reset(): void
            {
                $seen = [];
                foreach (['service.passthrough', 'service.inlined_dependency'] as $id) {
                    $instance = $this->get($id);
                    if (!$instance instanceof \Waffle\Commons\Contracts\Service\ResettableInterface) {
                        continue;
                    }
                    if (in_array($instance, $seen, true)) {
                        continue;
                    }
                    $seen[] = $instance;
                    $instance->reset();
                }
            }
        };

        $kernel = $this->makeKernel($runtime, new RecordingLogger());
        $kernel->configure();

        // Compiled container is in the path...
        static::assertInstanceOf(CompiledContainerInterface::class, $kernel->exposeContainer());

        $kernel->reset();
        $kernel->reset();

        // Two requests ⇒ two resets total (once each), NOT four — the duplicate
        // id never causes a second reset() within a single request.
        static::assertSame(2, $service->resets, 'reset() runs at most once per request, not once per id');
    }
}
