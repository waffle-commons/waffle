<?php

declare(strict_types=1);

namespace Waffle\Core;

use Waffle\Abstract\AbstractKernel;use Waffle\Abstract\AbstractSystem;use Waffle\Commons\Contracts\Config\ConfigInterface;use Waffle\Commons\Contracts\Config\Exception\InvalidConfigurationExceptionInterface;use Waffle\Commons\Contracts\Container\ContainerInterface;use Waffle\Commons\Contracts\Core\KernelInterface;use Waffle\Commons\Security\Exception\SecurityException;use Waffle\Commons\Security\Security;use Waffle\Kernel;use Waffle\Router\Router;

class System extends AbstractSystem
{
    public function __construct(Security $security)
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
            /** @var ContainerInterface $container */
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
