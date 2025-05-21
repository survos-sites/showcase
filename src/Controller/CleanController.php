<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class CleanController extends AbstractController
{
    #[Route('/clean', name: 'app_clean')]
    public function index(): Response
    {
        return $this->render('clean/index.html.twig', ['controller_name' => 'CleanController']);
    }
        public  function __construct(
        private readonly ProjectRepository $repo
    ) {
    }
}