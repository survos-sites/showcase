<?php

namespace App\Tests;

use App\Command\LoadProductsCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Console\Test\InteractsWithConsole;

class AppLoadDataCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    public function test_can_load_products(): void
    {
        $this->executeConsoleCommand('app:load-data ')
            ->assertSuccessful() // command exit code is 0
            ->assertOutputContains('Projects')
            ->assertOutputNotContains('failed')
        ;

    }
}
