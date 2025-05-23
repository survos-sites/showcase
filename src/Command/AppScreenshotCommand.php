<?php

namespace App\Command;

use Bakame\TabularData\HtmlTable\Parser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Panther\Client;

#[AsCommand('app:screenshot', 'take screenshot')]
final class AppScreenshotCommand
{

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'use .wip sites')]
        bool         $dev = false,
    ): int
    {

        // of interest: https_proxy=$(symfony proxy:url) curl https://my-domain.wip

        $sites = Parser::new()
            ->ignoreTableHeader()
            ->tableHeader(['dir', 'port', 'domains'])
            ->parseFile('http://127.0.0.1:7080');

        $client = Client::createChromeClient(
            null,
            [
            '--window-size=1500,4000',
            '--proxy-server=http://127.0.0.1:7080'
            ]
        );
        //let s use firefox
        //$client = Client::createFirefoxClient();
        foreach ($sites as $idx => $site) {
            if (is_numeric($site['port'])) {
                if (!empty($site['domains'])) {
                    $url = $site['domains'];
                    $host = parse_url($url, PHP_URL_HOST);
                    $io->warning($url);
                    $client->request('GET', $url);
                    $client->takeScreenshot($fn = "public/$host.png");
                    $base = "https://showcase.wip";
                    $link = "$host.png";
                    $io->writeln("<href=$base/$link>$link</>");
                }
            }
        }

        return Command::SUCCESS;

    }
}
