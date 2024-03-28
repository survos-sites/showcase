<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('app/index.html.twig', [
            'projects' => $projectRepository->findAll()
        ]);
    }
}
