<?php

namespace App\Controller\Api\Logs;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LogsController extends AbstractController{
    #[Route('/api/logs/logs', name: 'app_api_logs_logs')]
    public function index(): Response
    {
        return $this->render('api/logs/logs/index.html.twig', [
            'controller_name' => 'LogsController',
        ]);
    }
}
