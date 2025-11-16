<?php

use Castor\Attribute\AsTask;

use function Castor\{io,run,capture,import};
import('.castor/vendor/tacman/castor-tools/castor.php');
//import('src/Command/LoadDataCommand.php');
import(__DIR__ . '/src/Command/LoadDataCommand.php');
import(__DIR__ . '/src/Command/AppScreenshotCommand.php');
import(__DIR__ . '/src/Command/CiineLoadCommand.php');
//import(__DIR__ . '/src/Command/HelloCommand.php');

#[AsTask('ciine:import', description: 'Import the .jsonl created in load:ciine')]
function ciine_import(): void
{
    run('bin/console import:entities Ciine --file data/asciinema.explore.jsonl ');
}

#[AsTask('ciine:download', description: 'dispatch download requests from ciine entities')]
function ciine_download(): void
{
    run('bin/console iterate Ciine --transition=download --marking=basic  -vv');
    io()->write("make sure that\n\nbin/console mess:consume ciine.download\n\n is running");
}


#[AsTask(description: 'Welcome to Castor!')]
function hello(): void
{
    $currentUser = capture('whoami');

    io()->title(sprintf('Hello %s!', $currentUser));
}
