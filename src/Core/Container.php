<?php

declare(strict_types=1);

namespace Waffle\Core;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Commons\Contracts\Security\Exception\SecurityExceptionInterface;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;

/**
 * The Core Container acts as a secure decorator around ANY PSR-11 Container.
 * It enforces security rules on retrieved instances.
 */
final class Container implements ContainerInterface, PsrContainerInterface
{
    /**
     * @param PsrContainerInterface $inner The raw PSR-11 container implementation.
     * @param SecurityInterface $security The security layer.
     */
    public function __construct(
        private readonly PsrContainerInterface $inner,
        private readonly SecurityInterface $security,
    ) {}

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function get(string $id): object
    {
        try {
            // 1. Delegate resolution to the inner PSR-11 container
            /** @var object $instance */
            $instance = $this->inner->get($id);

            // 2. Apply security analysis
            $this->security->analyze($instance);

            return $instance;
        } catch (NotFoundExceptionInterface $e) {
            throw new NotFoundException($e->getMessage(), (int) $e->getCode());
        } catch (ContainerExceptionInterface $e) {
            throw new ContainerException($e->getMessage(), (int) $e->getCode());
        } catch (SecurityExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContainerException($e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function has(string $id): bool
    {
        return $this->inner->has($id);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function set(string $id, object|callable|string $concrete): void
    {
        // We attempt to call set() on the inner container if it supports it.
        // Since PSR-11 is read-only, this relies on the inner container having a set() method (like Waffle's).
        if (method_exists($this->inner, 'set')) {
            $this->inner->set($id, $concrete);
        } else {
            throw new ContainerException("The inner container does not support mutable 'set' operations.");
        }
    }
}
