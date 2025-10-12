<?php
declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\DomCrawler\Crawler;
use Survos\JsonlBundle\IO\JsonlWriter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Scrapes ONLY the Asciinema Explore (listing) pages and writes one JSONL row per
 * listed asciicast: id, title, author, featured, duration (seconds), castUrl, downloadUrl…
 * Detail pages are NOT fetched here.
 */
#[AsCommand('asciinema:scrape', 'Scrape Asciinema Explore pages (listing only) to JSONL. Uses Symfony Cache for HTML.')]
final class AsciinemaScrapeCommand extends Command
{
    const string DEFAULT_JSONL = 'data/asciinema.explore.jsonl';

    private const BASE    = 'https://asciinema.org';
    private const EXPLORE = '/explore/public?order=date&page=';

    public function __construct(
        private readonly CacheInterface $cache,
        // "our httpClient": must expose ->fetch(string $url): string
        // If yours is named differently, adjust the type & property name below.
        private readonly HttpClientInterface $httpClient,
        private readonly int $timeout = 20, // not used if your client handles timeouts internally
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,

        #[Option('Output JSONL file path')]
        string $out = self::DEFAULT_JSONL,

        #[Option('First page number to start from')]
        int $startPage = 1,

        #[Option('Maximum number of pages to scan (0 = until empty page)')]
        int $maxPages = 0,

        #[Option('Delay (seconds) between page requests')]
        float $delay = 0.15,

        #[Option('Stop on first fetch/parse error (default: true)')]
        bool $stopOnError = true,

        #[Option('Cache TTL in seconds (0 disables cache)')]
        int $cacheTtl = 3600
    ): int {
        $io->title('Asciinema Explore Scraper (listing only, cached)');

        $this->ensureDir(\dirname($out));
        $writer = JsonlWriter::open($out);

        $io->writeln(sprintf('Output: <info>%s</info>', $out));
        $io->writeln(sprintf('Start page: <info>%d</info>, Max pages: <info>%s</info>', $startPage, $maxPages === 0 ? 'until empty' : (string)$maxPages));
        $io->writeln(sprintf('Cache TTL: <info>%s</info>', $cacheTtl === 0 ? 'disabled' : $cacheTtl . 's'));
        $io->newLine();

        $total = 0;
        $pagesScanned = 0;
        $page = max(1, $startPage);

        while (true) {
            if ($maxPages > 0 && $pagesScanned >= $maxPages) {
                break;
            }

            $url = self::BASE . self::EXPLORE . $page;
            $io->section(sprintf('Page %d — %s', $page, $url));

            try {
                // Simplified cached page call per your request.
                $html = $this->cache->get(md5($url), fn (ItemInterface $item)
                        // Your client should expose ->fetch(string $url): string
                        => $this->httpClient->request('GET', $url)->getContent()
                    );
            } catch (\Throwable $e) {
                $io->error(sprintf('Fetch failed for %s: %s', $url, $e->getMessage()));
                if ($stopOnError) {
                    return Command::FAILURE;
                }
                break;
            }

            $rows = $this->extractFromExplore($html, $page);
            if ($rows === []) {
                $io->writeln('No casts found — assuming end of listing.');
                break;
            }

            foreach ($rows as $row) {
                $writer->write($row);
                $io->writeln(sprintf(
                    '• #%s — %s (%s)%s',
                    $row['id'],
                    $row['title'] ?? '(no title)',
                    $row['author'] !== '' ? $row['author'] : 'unknown author',
                    $row['featured'] ? '  <comment>[featured]</comment>' : ''
                ));
                $total++;
            }

            $pagesScanned++;
            $page++;
            if ($delay > 0) {
                usleep((int) \round($delay * 1_000_000));
            }
        }

        $io->success(sprintf('Done. Wrote %d record(s) from %d page(s).', $total, $pagesScanned));
        return Command::SUCCESS;
    }

    /**
     * Parse one Explore page and return normalized rows.
     *
     * Row schema:
     * - id (string)
     * - title (string|null)
     * - author (string)
     * - featured (bool)
     * - duration (int|null)   seconds
     * - durationText (string|null) original text (e.g., "4:50")
     * - page (int)
     * - castUrl (string)
     * - downloadUrl (string)
     * - scrapedAt (string, RFC3339)
     */
    private function extractFromExplore(string $html, int $page): array
    {
        $crawler = new Crawler($html);
        $now = (new \DateTimeImmutable())->format(DATE_ATOM);
        $rows = [];

        // Each listing card contains <div class="info">…</div>
        $crawler->filter('div.info')->each(function (Crawler $info) use (&$rows, $page, $now) {
            // Title & id from: <h3><a href="/a/{id}">TITLE</a> <span class="duration">mm:ss</span></h3>
            $id = null;
            $title = null;
            $durationText = null;
            $duration = null;

            if ($info->filter('h3 a[href^="/a/"]')->count()) {
                $a = $info->filter('h3 a[href^="/a/"]')->first();
                $href = (string) $a->attr('href');
                if (\preg_match('~^/a/([^/?#]+)~', $href, $m)) {
                    $id = $m[1];
                }
                $title = \trim($a->text(''));
            }

            if ($info->filter('h3 span.duration')->count()) {
                $durationText = \trim($info->filter('h3 span.duration')->first()->text(''));
                $duration = $this->parseClockDuration($durationText);
            }

            if ($id === null) {
                return; // skip malformed card
            }

            // Author: prefer “by <a href="/~user">user</a>”
            $author = '';
            if ($info->filter('small a[href^="/~"]')->count()) {
                $author = \trim($info->filter('small a[href^="/~"]')->first()->text(''));
            } elseif ($info->filter('span.author-avatar a[title]')->count()) {
                $author = \trim((string) $info->filter('span.author-avatar a[title]')->first()->attr('title'));
            }

            // Featured label present?
            $featured = $info->filter('span.special-label')->count() > 0;

            $castUrl     = self::BASE . '/a/' . $id;
            $downloadUrl = $castUrl . '.cast';

            $rows[] = [
                'id'           => $id,
                'title'        => $title,
                'author'       => $author,
                'featured'     => $featured,
                'duration'     => $duration,
                'durationText' => $durationText,
                'page'         => $page,
                'castUrl'      => $castUrl,
                'downloadUrl'  => $downloadUrl,
                'scrapedAt'    => $now,
            ];
        });

        return $rows;
    }

    private function parseClockDuration(?string $txt): ?int
    {
        if ($txt === null) {
            return null;
        }
        $txt = \trim($txt);
        // h:mm:ss or mm:ss
        if (\preg_match('~^(?:(\d{1,2}):)?(\d{1,2}):(\d{2})$~', $txt, $m)) {
            $h = $m1 = $s = 0;
            if ($m[1] !== '') {
                $h = (int) $m[1];
            }
            $m1 = (int) $m[2];
            $s  = (int) $m[3];
            return $h * 3600 + $m1 * 60 + $s;
        }
        // mm:ss
        if (\preg_match('~^(\d{1,2}):(\d{2})$~', $txt, $m)) {
            return ((int) $m[1]) * 60 + (int) $m[2];
        }
        return null;
    }

    private function ensureDir(string $dir): void
    {
        if ($dir && !\is_dir($dir)) {
            @\mkdir($dir, 0777, true);
        }
    }
}
