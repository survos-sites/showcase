<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Site>
 */
class SiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Site::class);
    }

    /** @return Site[] keyed by site code */
    public function findByLocalPort(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.localPort IS NOT NULL')
            ->getQuery()
            ->getResult();
    }
}
