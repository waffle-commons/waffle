<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Waffle\Core\Config;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Commons\Contracts\Container\ContainerInterface;
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

    #[\Override]
    public function configure(): self
    {
        $this->config = new Config(
            configDir: $this->configDir,
            environment: $this->testEnvironment,
        );

        // If the container is pre-set (legacy tests), we skip the standard container creation
        // BUT we must still initialize the System, otherwise handle() fails.
        if ($this->container !== null) {
            // Manually init System logic duplicated from AbstractKernel::configure
            // because we are bypassing the parent method.
            $security = new Security(cfg: $this->config);
            $this->system = new System(security: $security)->boot(kernel: $this);

            return $this;
        }

        return parent::configure();
    }
}
