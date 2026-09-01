<?php

namespace App\Command;

use Castor\Attribute\AsSymfonyTask;
use App\Service\SymfonyProxy;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Panther\Client;

#[AsCommand('app:screenshot', 'take screenshot for all the sites')]
#[AsSymfonyTask('app:screenshots')]
final class AppScreenshotCommand
{

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'use .wip sites')]
        bool         $dev = false,
    ): int
    {

        $sites = SymfonyProxy::getSites();

        $client = Client::createChromeClient(
            null,
            [
            '--window-size=1500,4000',
            '--proxy-server=http://127.0.0.1:7080'
            ]
        );
        //let s use firefox
        //$client = Client::createFirefoxClient();
        $captured = 0;
        $skipped = [];
        $failed = [];

        foreach ($sites as $site) {
            if (!is_numeric($site['port']) || empty($site['domains'])) {
                continue; // no server listening for this directory
            }

            $url = $site['domains'][0];
            $host = parse_url($url, PHP_URL_HOST);

            // A wildcard domain (*.vd.wip) is a routing pattern, not an address.
            // Chrome answers ERR_TUNNEL_CONNECTION_FAILED, which used to abort the
            // whole run partway through -- 24 sites captured, the rest never tried.
            if ($host === null || str_contains($host, '*')) {
                $skipped[] = $url;
                continue;
            }

            $io->writeln(sprintf('  %s', $url));

            try {
                $client->request('GET', $url);
                $client->takeScreenshot("public/$host.png");
            } catch (\Throwable $e) {
                // One site that will not load is a finding about that site, not a
                // reason to stop looking at the others.
                $failed[$host] = str_contains($e->getMessage(), "\n")
                    ? strtok($e->getMessage(), "\n")
                    : $e->getMessage();
                continue;
            }

            ++$captured;
            $io->writeln(sprintf('    <href=https://showcase.wip/%s.png>%s.png</>', $host, $host));
        }

        $io->success(sprintf('%d screenshot(s) written to public/.', $captured));

        if ($skipped !== []) {
            $io->note(sprintf('Skipped %d wildcard domain(s): %s', count($skipped), implode(', ', $skipped)));
        }

        if ($failed !== []) {
            $io->warning(sprintf('%d site(s) did not render:', count($failed)));
            foreach ($failed as $host => $message) {
                $io->writeln(sprintf('  %-28s %s', $host, mb_substr($message, 0, 100)));
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
