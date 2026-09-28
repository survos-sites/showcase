<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PublicPortfolio;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    #[Route('/', name: 'app_homepage', methods: ['GET'])]
    public function index(PublicPortfolio $portfolio): Response
    {
        return $this->render('public/home.html.twig', ['sites' => $portfolio->sites()]);
    }
}
