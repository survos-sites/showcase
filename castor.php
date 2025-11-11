<?php

use Castor\Attribute\AsTask;

use function Castor\{io,run,capture,import};
import('.castor/vendor/tacman/castor-tools/castor.php');
//import('src/Command/LoadDataCommand.php');
import(__DIR__ . '/src/Command/LoadDataCommand.php');
import(__DIR__ . '/src/Command/AppScreenshotCommand.php');
//import(__DIR__ . '/src/Command/HelloCommand.php');

#[AsTask(description: 'Welcome to Castor!')]
function hello(): void
{
    $currentUser = capture('whoami');

    io()->title(sprintf('Hello %s!', $currentUser));
}