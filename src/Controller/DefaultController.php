<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;


class DefaultController
{
    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'API Diet is running',
            'status' => 'ok',
            'timestamp' => date('c'),
        ]);
    }
}
