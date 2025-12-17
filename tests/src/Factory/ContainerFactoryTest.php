<?php

declare(strict_types=1);

namespace WaffleTests\Factory;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Exception\Container\ContainerException;
use Waffle\Factory\ContainerFactory;
use WaffleTests\AbstractTestCase as TestCase;
use WaffleTests\Helper\Controller\TempController;
use WaffleTests\Helper\Service\TempService;

#[CoversClass(ContainerFactory::class)]
#[AllowMockObjectsWithoutExpectations]
final class ContainerFactoryTest extends TestCase
{
    private null|string $tempDir = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary directory for tests that need to write files
        $this->tempDir = sys_get_temp_dir() . '/waffle_test_factory_' . uniqid('', true);
        mkdir($this->tempDir, 0o777, true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Clean up the temporary directory
        if ($this->tempDir && is_dir($this->tempDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($files as $fileinfo) {
                $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
                $todo($fileinfo->getRealPath());
            }

            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testCreateRegistersServicesFromDirectory(): void
    {
        // On pointe directement sur le vrai répertoire des classes Helper de test.
        // L'autoloader de Composer se chargera de les trouver.
        $helperDir = __DIR__ . '/../Helper';

        // Action
        $container = $this->createRealContainer();
        $factory = new ContainerFactory();
        $factory->create(
            container: $container,
            directory: $helperDir,
        );

        // Assertions
        // On vérifie que la factory a bien enregistré les services dans le conteneur.
        static::assertTrue($container->has(id: TempService::class));
        static::assertTrue($container->has(id: TempController::class));
    }

    public function testCreateRegistersServicesFromFile(): void
    {
        // On pointe directement sur le vrai répertoire des classes Helper de test.
        // L'autoloader de Composer se chargera de les trouver.
        $helperDir = __DIR__ . '/../Helper/TempService.php';

        // Action
        $container = $this->createRealContainer();
        $factory = new ContainerFactory();
        $factory->create(
            container: $container,
            directory: $helperDir,
        );

        static::assertTrue($container->has(id: TempService::class));
    }

    public function testCreateThrowsExceptionForUnknownClassType(): void
    {
        // Arrange
        static::expectException(ContainerException::class);
        // The factory should report the full path of the problematic file.
        static::expectExceptionMessageMatches('/Service or class ".*invalid_file\.txt" not found\./');

        // Create a file that the factory is not designed to handle (e.g., not a .php file).
        $invalidFile = $this->tempDir . '/invalid_file.txt';
        file_put_contents($invalidFile, 'This is not a PHP class.');

        // Action
        $container = $this->createRealContainer();
        $factory = new ContainerFactory();
        $factory->create(
            container: $container,
            directory: $this->tempDir,
        );
    }
}
