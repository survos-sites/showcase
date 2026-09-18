<?php

namespace App\Tests\Crawl;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Survos\CrawlerBundle\Tests\BaseVisitLinksTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CrawlAsVisitorTest extends WebTestCase
{
	#[TestDox('$url returns $expected')]
	#[TestWith(['', 'App\Entity\User', '/', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-1/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-2/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-3/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-4/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-5/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-6/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-7/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-8/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-9/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-10/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-11/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-12/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-13/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-14/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-15/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-16/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-17/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-18/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-19/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-20/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-21/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-22/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-23/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-24/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-25/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-26/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-27/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-28/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-29/show', 200])]
	#[TestWith(['', 'App\Entity\User', '/component/fixture__component-30/show', 200])]
	public function testRoute(string $username, string $userClassName, string $url, string|int|null $expected): void
	{
		$client = self::createClient();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertTrue($em->getConnection()->getParams()['memory'] ?? false);
        (new \Doctrine\ORM\Tools\SchemaTool($em))->createSchema($em->getMetadataFactory()->getAllMetadata());
        for ($i = 1; $i <= 30; ++$i) {
            $component = new \App\Entity\Component('fixture/component-'.$i);
            $component->name = 'Component '.$i;
            $component->kind = \App\Enum\ComponentKind::App;
            $component->composerJson = ['name' => $component->composerName, 'require' => []];
            $em->persist($component);
        }
        $em->flush();
        $client->request('GET', $url);
        self::assertResponseStatusCodeSame((int) $expected);
	}
}
