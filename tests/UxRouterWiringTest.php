<?php

declare(strict_types=1);

namespace App\Tests;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;

/** Browser path() comes from symfony/ux-router: routes are dumped on warmup and the app maps `router`. */
final class UxRouterWiringTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testWarmupDumpsRoutesAndTheRouterIsMapped(): void
    {
        $projectDir = dirname(__DIR__);
        // Reproduce a fresh checkout instead of passing on a stale route dump.
        (new Filesystem())->remove($projectDir.'/var/routes');
        self::bootKernel();
        $warmer = self::getContainer()->get('cache_warmer');
        $warmer->enableOptionalWarmers();
        $warmer->warmUp(self::$kernel->getCacheDir());

        self::assertFalse(class_exists('FOS\\JsRoutingBundle\\FOSJsRoutingBundle'));
        $dumped = file_get_contents($projectDir.'/var/routes/index.js');
        self::assertStringContainsString('"component_show"', $dumped);
        self::assertStringContainsString('"survos_workflow_transition"', $dumped);

        $entries = require $projectDir.'/importmap.php';
        self::assertArrayHasKey('router', $entries);
        self::assertArrayHasKey('@symfony/ux-router', $entries);
        foreach (array_keys($entries) as $entry) {
            self::assertStringNotContainsString('@survos/js-twig/', $entry);
        }
        self::assertFileExists($projectDir.'/assets/router.js');
    }
}
