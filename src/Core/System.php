<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractKernel;
use Waffle\Abstract\AbstractSystem;
use Waffle\Exception\InvalidConfigurationException;
use Waffle\Exception\SecurityException;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\KernelInterface;
use Waffle\Kernel;
use Waffle\Router\Router;

class System extends AbstractSystem
{
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @throws InvalidConfigurationException
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
            /** @var Config $config */
            $config = $kernel->config;
            $this->security->analyze(
                object: $config,
                expectations: [
                    Config::class,
                ],
            );
            /** @var Container $container */
            $container = $kernel->container;
            /** @var string $controllers */
            $controllers = $config->getString(key: 'waffle.paths.controllers');
            /** @var string $root */
            $root = APP_ROOT;
            $this->registerRouter(router: new Router(
                directory: $root . DIRECTORY_SEPARATOR . $controllers,
                system: $this,
            )->boot(container: $container));
        } catch (SecurityException $e) {
            $e->throw(view: new View(data: $e->serialize()));
        }

        return $this;
    }
}
