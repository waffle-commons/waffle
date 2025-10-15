<?php

declare(strict_types=1);

namespace WaffleTests\Factory;

use Waffle\Factory\ContainerFactory;
use WaffleTests\Core\Helper\ServiceA;
use WaffleTests\Core\Helper\ServiceB;
use WaffleTests\TestCase;

final class ContainerFactoryTest extends TestCase
{
    public function testCreateRegistersServicesFromDirectory(): void
    {
        // On pointe directement sur le vrai répertoire des classes Helper de test.
        // L'autoloader de Composer se chargera de les trouver.
        $helperDir = __DIR__ . '/../Core/Helper';

        // Action
        $container = $this->createRealContainer();
        $factory = new ContainerFactory();
        $factory->create(
            container: $container,
            directory: $helperDir,
        );

        // Assertions
        // On vérifie que la factory a bien enregistré les services dans le conteneur.
        static::assertTrue($container->has(ServiceA::class));
        static::assertTrue($container->has(ServiceB::class));
    }
}
