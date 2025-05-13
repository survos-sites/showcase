<?php

namespace App\Tests\Crawl;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Survos\CrawlerBundle\Tests\BaseVisitLinksTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CrawlAsVisitorTest extends BaseVisitLinksTest
{
	#[TestDox('/$method $url ($route)')]
	#[TestWith(['', 'App\Entity\User', '/', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/1', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/2', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/3', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/4', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/5', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/6', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/7', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/8', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/9', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/10', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/11', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/12', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/13', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/14', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/15', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/16', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/17', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/18', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/19', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/20', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/21', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/22', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/23', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/24', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/25', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/26', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/27', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/28', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/29', 200])]
	#[TestWith(['', 'App\Entity\User', '/show/30', 200])]
	public function testRoute(string $username, string $userClassName, string $url, string|int|null $expected): void
	{
		parent::testWithLogin($username, $userClassName, $url, (int)$expected);
	}
}
