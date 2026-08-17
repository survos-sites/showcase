<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Reads the local `symfony proxy` index page to discover which .wip sites are running.
 *
 * Moved here from survos/core-bundle's SurvosUtils: showcase was the only caller in the
 * entire ecosystem, and core-bundle is being retired (survos/mono#21). Dev-only — the
 * proxy does not exist in production.
 */
final class SymfonyProxy
{
    public const string PROXY_URL = 'http://127.0.0.1:7080';

    /**
     * @return list<array{directory: string, port: int|null, code: string|null, domains: list<string>}>
     */
    public static function getSites(string $proxyUrl = self::PROXY_URL): array
    {
        $html = @file_get_contents($proxyUrl);
        if (false === $html) {
            return [];
        }

        preg_match_all(
            '#<tr><td>([^<]+)<td>(?:<a[^>]+>(\d+)</a>|[^<]+)<td>(.*?)<(?:tr|/tr)#s',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $sites = [];
        foreach ($matches as $match) {
            $directory = trim($match[1]);
            $port = !empty($match[2]) ? (int) $match[2] : null;

            // Every domain in the cell, whether rendered as a link or as plain text.
            preg_match_all('#https://[^<>"]+/#', $match[3], $domainMatches);
            $domains = array_values(array_unique($domainMatches[0]));

            // The short code is the first non-wildcard *.wip domain, e.g. https://showcase.wip/ -> showcase
            $code = null;
            foreach ($domains as $domain) {
                if (!str_contains($domain, '*') && preg_match('#https://([^./]+)\.wip/#', $domain, $codeMatch)) {
                    $code = $codeMatch[1];
                    break;
                }
            }

            $sites[] = [
                'directory' => $directory,
                'port' => $port,
                'code' => $code,
                'domains' => $domains,
            ];
        }

        return $sites;
    }
}
