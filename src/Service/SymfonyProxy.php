<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Reads the local `symfony proxy` JSON index to discover which .wip sites are running.
 *
 * Moved here from survos/core-bundle's SurvosUtils: showcase was the only caller in the
 * entire ecosystem, and core-bundle is being retired (survos/mono#21). Dev-only — the
 * proxy does not exist in production.
 */
final class SymfonyProxy
{
    public const string PROXY_URL = 'http://localhost:7080/index.json';

    /**
     * @return list<array{directory: string, port: int|null, code: string|null, domains: list<string>}>
     */
    public static function getSites(string $proxyUrl = self::PROXY_URL): array
    {
        $json = @file_get_contents($proxyUrl);
        if (false === $json) {
            return [];
        }

        try {
            $index = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!is_array($index)) {
            return [];
        }

        $sites = [];
        foreach ($index as $directory => $data) {
            if (!is_string($directory) || !is_array($data)) {
                continue;
            }

            $port = (int) ($data['port'] ?? 0) ?: null;
            $scheme = in_array($data['scheme'] ?? null, ['http', 'https'], true)
                ? $data['scheme']
                : 'http';
            $domains = [];
            foreach ($data['domains'] ?? [] as $domain) {
                if (!is_string($domain) || '' === $domain) {
                    continue;
                }

                $domains[] = str_contains($domain, '://')
                    ? rtrim($domain, '/') . '/'
                    : sprintf('%s://%s/', $scheme, $domain);
            }
            $domains = array_values(array_unique($domains));

            // The short code is the first non-wildcard *.wip domain, e.g. https://showcase.wip/ -> showcase
            $code = null;
            foreach ($domains as $domain) {
                if (!str_contains($domain, '*') && preg_match('#https?://([^./]+)\.wip/#', $domain, $codeMatch)) {
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

    /**
     * The running sites only, as `code => local port`.
     *
     * A site is running when the proxy gave it a port; a registered-but-stopped site
     * has an empty port cell. Of 48 rows on this machine, 4 had ports.
     *
     * Both callers (app:load and the homepage) previously derived this themselves and
     * had drifted: one looped every domain, the other took `$domains[0]` and so read
     * ssai's wildcard `https://*.ssai.wip/` as the code `*.ssai`, which matches no
     * component. Deriving it once is the point of this method.
     *
     * @return array<string, int>
     */
    public static function getRunningCodes(string $proxyUrl = self::PROXY_URL): array
    {
        $running = [];
        foreach (self::getSites($proxyUrl) as $site) {
            if (empty($site['port'])) {
                continue;
            }

            $found = false;
            foreach ($site['domains'] as $domain) {
                // Skip wildcards: `https://*.ssai.wip/` is a tenant pattern, not the
                // site's own address, and ssai lists it before its real domain.
                if (str_contains($domain, '*')) {
                    continue;
                }
                if (preg_match('#https?://([^./]+)\.wip/#', $domain, $m)) {
                    $running[$m[1]] = $site['port'];
                    $found = true;
                }
            }

            // A site attached with only a wildcard still has a server running. The
            // directory basename is what Component::$name holds, so it matches.
            if (!$found && $site['directory']) {
                $running[basename($site['directory'])] = $site['port'];
            }
        }

        return $running;
    }
}
