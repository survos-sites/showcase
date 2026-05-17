<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Component;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/component/{componentId}')]
class ComponentController extends AbstractController
{
    #[Route('/show', name: 'component_show', methods: [Request::METHOD_GET])]
    #[Template('show.html.twig')]
    public function show(Component $component): Response|array
    {
        return ['component' => $component];
    }
}
