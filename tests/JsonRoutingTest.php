<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;

final class JsonRoutingTest extends KernelTestCase
{
    protected static function getKernelClass(): string { return Kernel::class; }

    public function testGridRoutingWithoutFosOrManualImportmapEntry(): void
    {
        self::bootKernel();
        self::getContainer()->get('cache_warmer')->warmUp(self::$kernel->getCacheDir());
        self::assertFalse(class_exists('FOS\\JsRoutingBundle\\FOSJsRoutingBundle'));
        $data = json_decode(file_get_contents(dirname(__DIR__).'/var/js_twig_bundle/generated/routes.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data['routes']);
        self::assertNull(self::getContainer()->get('router')->getRouteCollection()->get('fos_js_routing_js'));
        $entries = require dirname(__DIR__).'/importmap.php';
        self::assertArrayNotHasKey('fos-routing', $entries);
        self::assertArrayNotHasKey('@survos/js-twig/generated/fos_routes.js', $entries);
        self::assertArrayNotHasKey('@survos/js-twig/routing', $entries);
        $mapper = self::getContainer()->get(AssetMapperInterface::class);
        $runtime = $mapper->getAsset('@survos/js-twig/routing.js');
        self::assertNotNull($runtime);
        self::assertContains('@survos/js-twig/generated/routes.json', array_column($runtime->getJavaScriptImports(), 'assetLogicalPath'));
    }
}
