<?php

declare(strict_types=1);

namespace Waffle\Factory;

use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Utils\Service\ClassParser;
use Waffle\Exception\Container\ContainerException;

final class ContainerFactory
{
    public function __construct(
        private readonly ClassParser $parser = new ClassParser(),
    ) {}

    public function create(ContainerInterface $container, ?string $directory = null): void
    {
        $this->registerServices(container: $container, directory: $directory);
    }

    private function registerServices(ContainerInterface $container, ?string $directory = null): void
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

                $container->set(id: $class, concrete: $class);
            }
        }
    }

    /**
     * @return list<string>
     * @throws ContainerException
     */
    private function scanDirectory(string $directory): array
    {
        /** @var list<string> $files */
        $files = [];
        $paths = scandir($directory);

        if (!$paths) {
            return $files;
        }

        foreach ($paths as $path) {
            $currentDir = $path === Constant::CURRENT_DIR;
            $previousDir = $path === Constant::PREVIOUS_DIR;
            $dsStore = str_contains($path, Constant::DS_STORE);
            if ($currentDir || $previousDir || $dsStore) {
                continue;
            }

            $file = $directory . DIRECTORY_SEPARATOR . $path;

            if (is_dir($file)) {
                foreach ($this->scanDirectory($file) as $nested) {
                    if (!is_string($nested)) {
                        continue;
                    }
                    $files[] = $nested;
                }
                continue;
            }

            if (str_contains($path, Constant::PHPEXT)) {
                $className = $this->parser->className($file);
                if (is_string($className)) {
                    $files[] = $className;
                }
                continue;
            }

            throw new ContainerException("Service or class \"{$file}\" not found.");
        }

        return $files;
    }
}
