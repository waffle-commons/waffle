<?php

declare(strict_types=1);

namespace Waffle\Core;

use ReflectionException;
use Waffle\Abstract\AbstractKernel;
use Waffle\Abstract\AbstractSystem;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Contracts\Security\Exception\SecurityExceptionInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Exception\WaffleException;
use Waffle\Kernel;

class System extends AbstractSystem
{
    public function __construct(SecurityInterface $security)
    {
        $this->security = $security;
    }

    /**
     * @throws WaffleException
     */
    #[\Override]
    public function boot(KernelInterface $kernel): self
    {
        try {
            /** @var Kernel $kernel */
            $this->security->analyze(object: $kernel, expectations: [
                Kernel::class,
                AbstractKernel::class,
                KernelInterface::class,
            ]);
            /** @var ConfigInterface $config */
            $config = $kernel->config;
            $this->security->analyze(object: $config, expectations: [
                ConfigInterface::class,
            ]);
        } catch (SecurityExceptionInterface|ReflectionException $e) {
            throw new WaffleException(message: $e->getMessage(), code: $e->getCode(), previous: $e);
        }

        return $this;
    }
}
