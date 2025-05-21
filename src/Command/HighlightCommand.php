<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Ahc\CliSyntax\Highlighter;

#[AsCommand('app:highlight', 'hilight code on the terminal')]
class HighlightCommand
{
	public function __construct()
	{
	}


	public function __invoke(
		SymfonyStyle $io,
		#[Argument('name of a php file')]
		string $filename,
	): int
	{


// PHP code
        echo new Highlighter('<?php echo "Hello world!";');
// OR
        $x =  (new Highlighter)->highlight('<?php echo "Hello world!";');
        $io->writeln($x);

// PHP file
        echo Highlighter::for($filename);
		if ($filename) {
            $x =  (new Highlighter)->highlight('<?php echo "Hello world!";');
		    $io->writeln("Argument filename: $filename");
		}
		$io->success(self::class . " success.");
		return Command::SUCCESS;
	}
}
