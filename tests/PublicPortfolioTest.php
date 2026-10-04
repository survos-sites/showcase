<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Component;
use App\Service\PublicPortfolio;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

final class PublicPortfolioTest extends WebTestCase
{
    public function testHomepagePublishesOnlySelectedSites(): void
    {
        $client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        $internal = new Component('survos-sites/harvest');
        $internal->description = 'Internal harvest operations';
        $internal->localDir = '/private/workspace/harvest';
        $em->persist($internal);
        $em->flush();
        $portfolio = self::getContainer()->get(PublicPortfolio::class);
        $io = new SymfonyStyle(new ArrayInput([]), new BufferedOutput());
        $portfolio->load($io);
        $portfolio->load($io);
        self::assertSame(4, $em->getRepository(Component::class)->count([]));
        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(3, '#work a.card');
        self::assertSelectorExists('#work a.card[href="https://recordia.org"]');
        self::assertSelectorExists('a[href="https://github.com/survos"][target="_blank"]');
        self::assertSelectorExists('a[href="https://medium.com/@tacman1123"][target="_blank"]');
        self::assertStringNotContainsString('harvest', $client->getResponse()->getContent());
        self::assertStringNotContainsString('/private/workspace', $client->getResponse()->getContent());
    }
}
