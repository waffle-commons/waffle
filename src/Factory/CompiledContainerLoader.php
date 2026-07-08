<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Waffle\Commons\Contracts\Container\CompiledContainerInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;

/**
 * AOT-01 kernel fast-path loader.
 *
 * Given the fully-booted, locked runtime container, the loader returns a
 * {@see CompiledContainerInterface} wrapping it when ALL of the following hold:
 *
 *   1. The AOT fast path is explicitly enabled (`WAFFLE_AOT=1` in the environment).
 *   2. A compiled artifact exists at the configured path AND loads without error.
 *   3. The artifact defines the expected class, it implements
 *      {@see CompiledContainerInterface}, and it composes the runtime container
 *      without throwing.
 *
 * On ANY miss — disabled, missing/corrupt artifact, wrong class, construction
 * failure — the loader logs a warning and returns the runtime container
 * **unchanged** (RFC-019 mandatory fallback). The default (no env var) keeps dev
 * on the reflection path, so behaviour is unaffected unless AOT is opted into.
 *
 * Staleness (AOT-04): the loader performs NO cross-component fingerprint — it
 * cannot tell whether the artifact matches the current code. A stale artifact
 * would silently serve an outdated graph, so a successful load emits a prominent
 * {@see LoggerInterface::warning()} reminding the operator that they MUST
 * regenerate the artifact (`bin/waffle container:compile`) after ANY code change.
 * Verification is deliberately left self-contained: it gates the load behind the
 * env flag AND the artifact existing AND implementing
 * {@see CompiledContainerInterface} AND loading without throwing.
 *
 * Perimeter note: the loader depends only on the container **contracts** — it
 * never references the concrete `Waffle\Commons\Container\Container`. The
 * generated compiled class composes that concrete internally; if the supplied
 * runtime container is not of the expected concrete type, construction throws and
 * the loader falls back (the correct, safe outcome).
 */
final readonly class CompiledContainerLoader
{
    /** Environment flag that opts a worker into the compiled-container fast path. */
    public const string ENV_FLAG = 'WAFFLE_AOT';

    /** Default generated artifact path, relative to the application root. */
    public const string DEFAULT_ARTIFACT = 'var/cache/CompiledContainer.php';

    /** Default FQCN emitted by the container compiler (AOT-01). */
    public const string DEFAULT_CLASS = 'Waffle\\Generated\\CompiledContainer';

    /** Build command an operator MUST re-run after any code change (AOT-04). */
    public const string REBUILD_COMMAND = 'bin/waffle container:compile';

    public function __construct(
        private string $artifactPath,
        private string $compiledClass = self::DEFAULT_CLASS,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Returns the compiled container wrapping $runtime when the fast path is
     * enabled and the artifact loads cleanly; otherwise returns $runtime unchanged.
     */
    public function load(ContainerInterface $runtime): ContainerInterface
    {
        if (!$this->isEnabled()) {
            // Default dev path: no env var, no AOT — behave exactly as before.
            return $runtime;
        }

        if (!is_file($this->artifactPath)) {
            $this->logger->warning('AOT fast path enabled but no compiled-container artifact was found; falling back to the reflection container.', [
                'artifact' => $this->artifactPath,
            ]);

            return $runtime;
        }

        try {
            require_once $this->artifactPath;

            if (!class_exists($this->compiledClass)) {
                $this->logger->warning('Compiled-container artifact did not define the expected class; falling back to the reflection container.', [
                    'class' => $this->compiledClass,
                ]);

                return $runtime;
            }

            $compiled = new $this->compiledClass($runtime);
            if (!$compiled instanceof CompiledContainerInterface) {
                $this->logger->warning('Compiled-container class does not implement CompiledContainerInterface; falling back to the reflection container.', [
                    'class' => $this->compiledClass,
                ]);

                return $runtime;
            }

            // AOT-04: the artifact is NOT fingerprinted against the current code,
            // so it CAN silently serve a stale graph. Make the operator's
            // responsibility loud and unmissable on every successful load.
            $this->logger->warning('AOT compiled container loaded — this artifact is NOT validated against the current code; '
            . 'a stale artifact serves an OUTDATED service graph. You MUST regenerate it with "'
            . self::REBUILD_COMMAND
            . '" after ANY code change.', [
                'artifact' => $this->artifactPath,
                'class' => $this->compiledClass,
                'rebuild_command' => self::REBUILD_COMMAND,
            ]);

            return $compiled;
        } catch (Throwable $e) {
            $this->logger->warning('Failed to load the compiled container; falling back to the reflection container.', [
                'artifact' => $this->artifactPath,
                'error' => $e->getMessage(),
            ]);

            return $runtime;
        }
    }

    /**
     * Whether the `WAFFLE_AOT` environment flag is set to an on value (`1`/`true`).
     */
    private function isEnabled(): bool
    {
        $value = getenv(self::ENV_FLAG);
        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
    }
}
