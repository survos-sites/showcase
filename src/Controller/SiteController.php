<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SiteController extends AbstractController
{
    #[Route('/browse', name: 'app_browse', methods: ['GET'])]
    public function browse(SiteRepository $sites): Response
    {
        return $this->render('site/browse.html.twig', [
            'sites' => $sites->createQueryBuilder('site')
                ->leftJoin('site.component', 'component')->addSelect('component')
                ->orderBy('site.id', 'ASC')->getQuery()->getResult(),
        ]);
    }
}
