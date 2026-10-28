<?php

declare(strict_types=1);

namespace Fiskardex\Http\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SystemController
{
    public function __construct(private readonly string $indexPath)
    {
    }

    public function home(Request $request, array $match): Response
    {
        return new Response(
            file_get_contents($this->indexPath),
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    public function health(Request $request, array $match): Response
    {
        return new JsonResponse(['status' => 'ok', 'service' => 'fiskardex-mvp']);
    }
}
