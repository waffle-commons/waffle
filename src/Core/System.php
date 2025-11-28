<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractKernel;
use Waffle\Abstract\AbstractSystem;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Config\Exception\InvalidConfigurationExceptionInterface;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Contracts\Security\Exception\SecurityExceptionInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Kernel;
use Waffle\Trait\RenderingTrait;

class System extends AbstractSystem
{
    use RenderingTrait;

    public function __construct(SecurityInterface $security)
    {
        $this->security = $security;
    }

    /**
     * @throws InvalidConfigurationExceptionInterface
     */
    #[\Override]
    public function boot(KernelInterface $kernel): self
    {
        try {
            /** @var Kernel $kernel */
            $this->security->analyze(
                object: $kernel,
                expectations: [
                    Kernel::class,
                    AbstractKernel::class,
                    KernelInterface::class,
                ],
            );
            /** @var ConfigInterface $config */
            $config = $kernel->config;
            $this->security->analyze(
                object: $config,
                expectations: [
                    ConfigInterface::class,
                ],
            );
        } catch (SecurityExceptionInterface $e) {
            $this->throw(view: new View(data: $e->serialize()));
        }

        return $this;
    }
}
