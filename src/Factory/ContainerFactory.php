<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Waffle\Core\Constant;
use Waffle\Core\Container;
use Waffle\Interface\ContainerInterface;
use Waffle\Trait\ReflectionTrait;

final class ContainerFactory
{
    use ReflectionTrait;

    public function create(null|string $serviceDir = null): ContainerInterface
    {
        $container = new Container();
        $this->registerServices(
            container: $container,
            serviceDir: $serviceDir,
        );

        return $container;
    }

    private function registerServices(ContainerInterface $container, null|string $serviceDir = null): void
    {
        if (null !== $serviceDir) {
            if (!is_dir($serviceDir)) {
                return;
            }

            $files = $this->scanDirectory($serviceDir);

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

                if (is_dir($file)) {
                    $files = array_merge($files, $this->scanDirectory($file));
                } elseif (str_contains($path, Constant::PHPEXT)) {
                    $files[] = $this->className($file);
                }
            }
        }

        return $files;
    }
}
