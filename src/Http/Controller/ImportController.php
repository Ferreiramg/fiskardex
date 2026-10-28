<?php

declare(strict_types=1);

namespace Fiskardex\Http\Controller;

use Fiskardex\Application\Import\DeleteImport;
use Fiskardex\Application\Import\GetImport;
use Fiskardex\Application\Import\GetImportCollection;
use Fiskardex\Application\Import\ListImports;
use Fiskardex\Application\Import\UploadImport;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ImportController
{
    public function __construct(
        private readonly ListImports $list,
        private readonly GetImport $get,
        private readonly GetImportCollection $collection,
        private readonly UploadImport $upload,
        private readonly DeleteImport $remove,
    ) {
    }

    public function index(Request $request, array $match): Response
    {
        return new JsonResponse(($this->list)());
    }

    public function create(Request $request, array $match): Response
    {
        $result = ($this->upload)($request->files->get('file'));

        return new JsonResponse($result['response'], $result['status']);
    }

    public function show(Request $request, array $match): Response
    {
        $import = ($this->get)($match['id']);

        return $import ? new JsonResponse($import) : new JsonResponse(['title' => 'Not found'], 404);
    }

    public function delete(Request $request, array $match): Response
    {
        return ($this->remove)($match['id']) === null
            ? new JsonResponse(['title' => 'Not found'], 404)
            : new Response('', 204);
    }

    public function records(Request $request, array $match): Response
    {
        return $this->collectionResponse($match['id'], 'records');
    }

    public function documents(Request $request, array $match): Response
    {
        return $this->collectionResponse($match['id'], 'documents');
    }

    public function issues(Request $request, array $match): Response
    {
        return $this->collectionResponse($match['id'], 'issues');
    }

    private function collectionResponse(string $id, string $key): Response
    {
        $items = ($this->collection)($id, $key);

        return $items === null ? new JsonResponse(['title' => 'Not found'], 404) : new JsonResponse($items);
    }
}
