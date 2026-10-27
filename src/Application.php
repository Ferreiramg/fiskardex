<?php

declare(strict_types=1);

namespace Fiskardex;

use Fiskardex\Fiscal\EfdParser;
use Fiskardex\Fiscal\XmlParser;
use Fiskardex\Storage\JsonStore;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Uid\Uuid;

final class Application
{
    private JsonStore $store;

    private string $uploads;

    private RouteCollection $routes;

    public function __construct(private readonly string $root)
    {
        $data = $root . '/var/data';
        $this->uploads = $data . '/uploads';

        if (!is_dir($this->uploads)) {
            mkdir($this->uploads, 0775, true);
        }

        $this->store = new JsonStore($data . '/imports.json');
        $this->routes = new RouteCollection();
        $this->configureRoutes();
    }

    public function handle(Request $request): Response
    {
        try {
            $context = (new RequestContext())->fromRequest($request);
            $match = (new UrlMatcher($this->routes, $context))->match($request->getPathInfo());

            return $this->dispatch($match['_handler'], $request, $match);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'title' => 'Internal Server Error',
                'detail' => $exception->getMessage(),
            ], 500);
        }
    }

    private function configureRoutes(): void
    {
        $this->routes->add('home', new Route('/', ['_handler' => 'home']));
        $this->routes->add('health', new Route('/health', ['_handler' => 'health']));
        $this->routes->add(
            'imports',
            new Route('/api/v1/imports', ['_handler' => 'imports'], [], [], '', [], ['GET', 'POST']),
        );
        $this->routes->add(
            'import',
            new Route('/api/v1/imports/{id}', ['_handler' => 'import'], ['id' => '[0-9a-f-]+']),
        );
        $this->routes->add(
            'records',
            new Route('/api/v1/imports/{id}/records', ['_handler' => 'records'], ['id' => '[0-9a-f-]+']),
        );
        $this->routes->add(
            'documents',
            new Route('/api/v1/imports/{id}/documents', ['_handler' => 'documents'], ['id' => '[0-9a-f-]+']),
        );
        $this->routes->add(
            'issues',
            new Route('/api/v1/imports/{id}/issues', ['_handler' => 'issues'], ['id' => '[0-9a-f-]+']),
        );
    }

    private function dispatch(string $handler, Request $request, array $match): Response
    {
        return match ($handler) {
            'home' => new Response(
                file_get_contents($this->root . '/public/index.html'),
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            ),
            'health' => new JsonResponse(['status' => 'ok', 'service' => 'fiskardex-mvp']),
            'imports' => $request->isMethod('POST') ? $this->upload($request) : new JsonResponse($this->store->all()),
            'import' => $this->one($match['id']),
            'records' => $this->collection($match['id'], 'records'),
            'documents' => $this->collection($match['id'], 'documents'),
            'issues' => $this->collection($match['id'], 'issues'),
            default => new JsonResponse(['title' => 'Not found'], 404),
        };
    }

    private function upload(Request $request): Response
    {
        $file = $request->files->get('file');

        if (!$file || !$file->isValid()) {
            return new JsonResponse([
                'title' => 'Invalid upload',
                'detail' => 'Envie um arquivo no campo file.',
            ], 400);
        }

        if ($file->getSize() > 104857600) {
            return new JsonResponse(['title' => 'File too large'], 413);
        }

        $name = basename((string) $file->getClientOriginalName());
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name) ?: 'upload';
        $id = Uuid::v4()->toRfc4122();
        $stored = $id . '-' . $safeName;
        $path = $this->uploads . '/' . $stored;
        $file->move($this->uploads, $stored);
        $hash = hash_file('sha256', $path);

        if ($existing = $this->store->byHash($hash)) {
            return new JsonResponse($existing);
        }

        $lower = strtolower($name);
        $kind = str_ends_with($lower, '.xml')
            ? 'nfe-xml'
            : (str_ends_with($lower, '.txt') || str_ends_with($lower, '.efd') ? 'efd' : 'unknown');
        $job = [
            'id' => $id,
            'originalName' => $name,
            'storedName' => $stored,
            'mimeType' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => filesize($path),
            'sha256' => $hash,
            'status' => 'processing',
            'kind' => $kind,
            'createdAt' => date(DATE_ATOM),
            'encoding' => 'unknown',
            'period' => [],
            'recordCounts' => [],
            'records' => [],
            'documents' => [],
            'issues' => [],
        ];

        if ($kind === 'efd') {
            $job = (new EfdParser())->parse($path, $job);
        } elseif ($kind === 'nfe-xml') {
            $job = (new XmlParser())->parse($path, $job);
        } else {
            $job['status'] = 'quarantined';
            $job['issues'][] = [
                'id' => Uuid::v4()->toRfc4122(),
                'severity' => 'error',
                'code' => 'UNSUPPORTED_FILE',
                'message' => 'Formato não suportado. Use EFD/TXT ou NF-e XML.',
            ];
        }

        $this->store->save($job);

        return new JsonResponse([
            'id' => $id,
            'status' => $job['status'],
            'sha256' => $hash,
        ], 202);
    }

    private function one(string $id): Response
    {
        $job = $this->store->get($id);

        return $job
            ? new JsonResponse($job)
            : new JsonResponse(['title' => 'Not found'], 404);
    }

    private function collection(string $id, string $key): Response
    {
        $job = $this->store->get($id);

        return $job
            ? new JsonResponse($job[$key] ?? [])
            : new JsonResponse(['title' => 'Not found'], 404);
    }
}
