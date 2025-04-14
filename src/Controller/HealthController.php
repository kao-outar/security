<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return new JsonResponse(['message' => 'hello world']);
    }
}
