<?php

declare(strict_types=1);

namespace WaffleTests\Factory;

use PHPUnit\Framework\TestCase;
use Waffle\Attribute\Configuration;
use Waffle\Core\Container;
use Waffle\Core\Security;
use Waffle\Factory\ContainerFactory;
use WaffleTests\Core\Helper\ServiceA;
use WaffleTests\Core\Helper\ServiceB;

final class ContainerFactoryTest extends TestCase
{
    public function testCreateRegistersServicesFromDirectory(): void
    {
        // On pointe directement sur le vrai répertoire des classes Helper de test.
        // L'autoloader de Composer se chargera de les trouver.
        $helperDir = __DIR__ . '/../Core/Helper';

        // Action
        $config = new Configuration();
        $security = new Security(cfg: $config);
        $container = new Container(security: $security);
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
