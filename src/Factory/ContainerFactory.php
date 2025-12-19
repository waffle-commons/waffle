<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Utils\Trait\ReflectionTrait;
use Waffle\Exception\Container\ContainerException;

final class ContainerFactory
{
    use ReflectionTrait;

    public function create(ContainerInterface $container, null|string $directory = null): void
    {
        $this->registerServices(
            container: $container,
            directory: $directory,
        );
    }

    private function registerServices(ContainerInterface $container, null|string $directory = null): void
    {
        if (null !== $directory) {
            if (!is_dir($directory)) {
                return;
            }

            $files = $this->scanDirectory($directory);

            foreach ($files as $class) {
                if ($container->has(id: $class)) {
                    continue;
                }

                $container->set(
                    id: $class,
                    concrete: $class,
                );
            }
        }
    }

    /**
     * @return string[]
     * @throws ContainerException
     */
    private function scanDirectory(string $directory): array
    {
        $files = [];
        $paths = scandir($directory);

        if ($paths) {
            foreach ($paths as $path) {
                $currentDir = $path === Constant::CURRENT_DIR;
                $previousDir = $path === Constant::PREVIOUS_DIR;
                $dsStore = str_contains($path, Constant::DS_STORE);
                if ($currentDir || $previousDir || $dsStore) {
                    continue;
                }

                $file = $directory . DIRECTORY_SEPARATOR . $path;

                match (true) {
                    is_dir($file) => $files = array_merge($files, $this->scanDirectory($file)),
                    str_contains($path, Constant::PHPEXT) => $files[] = $this->className($file),
                    default => throw new ContainerException("Service or class \"{$file}\" not found."),
                };
            }
        }

        return $files;
    }
}
