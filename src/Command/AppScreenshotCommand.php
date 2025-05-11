<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;
use Symfony\Component\Panther\Client;
use Bakame\HtmlTable\Parser;

#[AsCommand('app:screenshot', 'take screenshot')]
final class AppScreenshotCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __invoke(
        IO $io,

        #[Option(description: 'use .wip sites')]
        bool $dev = true,
    ): int {

        // of interest: https_proxy=$(symfony proxy:url) curl https://my-domain.wip

$sites = Parser::new()
    ->ignoreTableHeader()
    ->tableHeader(['dir', 'port', 'domains'])
    ->parseFile('http://127.0.0.1:7080');

$client = Client::createChromeClient();
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

return self::SUCCESS;

// alternatively, create a Firefox client
//        $client = Client::createFirefoxClient();
        foreach (['mus.wip','en.mus.wip','ff.wip'] as $uri) {
            $client->request('GET', "https://$uri");
            $client->takeScreenshot($fn = "public/$uri.png");

            $base = "https://showcase.wip";
            $link = "$uri.png";
            $io->writeln("<href=$base/$link>$link</>");
        }
        $io->success($this->getName().' success: ');

//        $client->clickLink('Getting started');

// wait for an element to be present in the DOM, even if hidden
//        $crawler = $client->waitFor('#bootstrapping-the-core-library');
// you can also wait for an element to be visible
        $crawler = $client->waitForVisibility('#bootstrapping-the-core-library');

// get the text of an element thanks to the query selector syntax
        echo $crawler->filter('div:has(> #bootstrapping-the-core-library)')->text();
// take a screenshot of the current page
        $io->success($this->getName().' success: ' . $fn);

        return self::SUCCESS;
    }
}
