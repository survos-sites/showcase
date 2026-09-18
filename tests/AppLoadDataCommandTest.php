<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Component;
use App\Repository\{ComponentRepository, SiteRepository};
use App\Service\AppService;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Console\Test\InteractsWithConsole;

final class AppLoadDataCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function testLoadComponentsIsIdempotent(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $directory = sys_get_temp_dir().'/showcase-test-'.bin2hex(random_bytes(8));
        $fs = new Filesystem();
        try {
            $fs->mkdir([$directory.'/showcase', $directory.'/mono/bu/sample', $directory.'/mono/lib']);
            $fs->dumpFile($directory.'/mono/bu/sample/composer.json', json_encode([
                'name' => 'fixture/sample', 'type' => 'symfony-bundle', 'description' => 'Fixture bundle',
            ], JSON_THROW_ON_ERROR));
            $fs->dumpFile($directory.'/mono/bu/sample/OVERVIEW.md', 'Fixture overview');
            $container->set(AppService::class, new AppService(
                $container->get(ComponentRepository::class), $container->get(SiteRepository::class),
                $em, $directory.'/showcase',
            ));
            for ($i = 0; $i < 2; ++$i) {
                $this->executeConsoleCommand('app:load')->assertSuccessful()->assertOutputContains('Components: 1');
            }
            self::assertSame(1, $em->getRepository(Component::class)->count([]));
            self::assertSame('Fixture overview', $em->find(Component::class, 'fixture__sample')->overview);
        } finally {
            $fs->remove($directory);
        }
    }
}
