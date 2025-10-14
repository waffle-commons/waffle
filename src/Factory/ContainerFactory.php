<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Waffle\Core\Constant;
use Waffle\Exception\Container\ContainerException;use Waffle\Interface\ContainerInterface;
use Waffle\Trait\ReflectionTrait;

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
                    $container->set(
                        id: $class,
                        concrete: $class,
                    );
                }
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
                if ($path === Constant::CURRENT_DIR || $path === Constant::PREVIOUS_DIR) {
                    continue;
                }

                $file = $directory . DIRECTORY_SEPARATOR . $path;

                match (true) {
                    (is_dir($file)) => $files = array_merge($files, $this->scanDirectory($file)),
                    (str_contains($path, Constant::PHPEXT)) => $files[] = $this->className($file),
                    default => throw new ContainerException("Service or class \"{$file}\" not found.")
                };
            }
        }

        return $files;
    }
}
