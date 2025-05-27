<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Ahc\CliSyntax\Highlighter;

#[AsCommand('app:highlight', 'hilight code on the terminal')]
class HighlightCommand extends Command
{
//	public function __construct()
//	{
//	}


	public function __invoke(
		SymfonyStyle $io,
		#[Argument('name of a php file')]
		string $filename='',
        #[Option] bool $clear=false,
        #[Option] bool $reset =false,
	): int
	{
        $application = $this->getApplication();
        $greetInput = new ArrayInput([
            // the command name is passed as first argument
            'command' => 'messenger:stat',
//            '--format'  => 'json',
        ]);

        // disable interactive behavior for the greet command
        $greetInput->setInteractive(false);


        while (true) {
            $output = $io;
            $returnCode = $this->getApplication()->doRun($greetInput, $io);
//        dd($returnCode, $io->getOutput());
            $io->writeln($returnCode);
            $io->writeln("Time: " . random_int(0, 9999));
            sleep(2);

            if ($clear) {
                echo "\033[2J";
            }


        }
        if ($reset) {
            echo "\033";
        }

        $io->writeln(sprintf('<info>%s</info>', $filename));

// PHP code
        echo new Highlighter('<?php echo "Hello world!";');
        $x =  (new Highlighter)->highlight('<?php echo "Hello world!";');
        $io->writeln($x);

// PHP file
     	if ($filename) {
            echo Highlighter::for($filename);
            $x =  (new Highlighter)->highlight('<?php echo "Hello world!";');
		    $io->writeln("Argument filename: $filename");
		}
		$io->success(self::class . " success.");
		return Command::SUCCESS;
	}
}
