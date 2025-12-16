<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Core\System;
use Waffle\Kernel;

/**
 * This is a test double for the concrete Kernel.
 */
class WebKernel extends Kernel
{
    private string $configDir;
    private string $testEnvironment;

    public function __construct(
        string $configDir,
        string $environment,
        null|ContainerInterface $container = null,
        null|PsrContainerInterface $innerContainer = null,
        LoggerInterface $logger = new NullLogger(),
    ) {
        parent::__construct($logger);
        $this->configDir = $configDir;
        $this->testEnvironment = $environment;
        $this->container = $container;

        if ($innerContainer !== null) {
            $this->setContainerImplementation($innerContainer);
        }
    }

    public function setDeps(PsrContainerInterface $innerContainer): void
    {
        $this->setContainerImplementation($innerContainer);
    }

    #[\Override]
    public function configure(): self
    {
        $this->config = new class($this->configDir, $this->testEnvironment) implements ConfigInterface {
            public function __construct(
                private string $dir,
                private string $env,
            ) {}

            public function getInt(string $key, null|int $default = null): null|int
            {
                return 10;
            }

            public function getString(string $key, null|string $default = null): null|string
            {
                return null;
            }

            public function getArray(string $key, null|array $default = null): null|array
            {
                return [];
            }

            public function getBool(string $key, bool $default = false): bool
            {
                return false;
            }
        };

        // If the container is pre-set (legacy tests), we skip the standard container creation
        // BUT we must still initialize the System, otherwise handle() fails.
        if ($this->container !== null) {
            // Manually init System logic duplicated from AbstractKernel::configure
            // because we are bypassing the parent method.
            if ($this->security === null) {
                // Fallback mock if not set
                $this->security = new class implements SecurityInterface {
                    public function analyze(object $object, array $expectations = []): void
                    {
                    }
                };
            }
            $this->system = new System(security: $this->security)->boot(kernel: $this);

            return $this;
        }

        return parent::configure();
    }
}
