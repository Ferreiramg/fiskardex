<?php

declare(strict_types=1);

namespace Fiskardex;

use Fiskardex\Application\Import\DeleteImport;
use Fiskardex\Application\Import\GetImport;
use Fiskardex\Application\Import\GetImportCollection;
use Fiskardex\Application\Import\ListImports;
use Fiskardex\Application\Import\UploadImport;
use Fiskardex\Database\Migrator;
use Fiskardex\Http\Controller\ImportController;
use Fiskardex\Http\Controller\SystemController;
use Fiskardex\Http\RouteFactory;
use Fiskardex\Storage\SqliteImportStore;
use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

/** Application composition root and HTTP entry point. */
final class Application
{
    private UrlMatcher $matcher;

    /** @var array<string, callable(Request, array): Response> */
    private array $handlers;

    public function __construct(string $root)
    {
        $data = $root . '/var/data';
        $uploads = $data . '/uploads';
        if (!is_dir($uploads)) {
            mkdir($uploads, 0775, true);
        }

        $databasePath = getenv('APP_DATABASE_PATH') ?: $data . '/fiskardex.sqlite';
        $connection = new PDO('sqlite:' . $databasePath);
        (new Migrator($connection, $root . '/database/migrations'))->migrate();
        $store = new SqliteImportStore($connection);

        $imports = new ImportController(
            new ListImports($store),
            new GetImport($store),
            new GetImportCollection($store),
            new UploadImport($store, $uploads),
            new DeleteImport($store, $uploads),
        );
        $system = new SystemController($root . '/public/index.html');
        $routes = (new RouteFactory())->create();
        $this->matcher = new UrlMatcher($routes, new RequestContext());
        $this->handlers = [
            'home' => $system->home(...),
            'health' => $system->health(...),
            'imports.index' => $imports->index(...),
            'imports.create' => $imports->create(...),
            'imports.show' => $imports->show(...),
            'imports.delete' => $imports->delete(...),
            'imports.records' => $imports->records(...),
            'imports.documents' => $imports->documents(...),
            'imports.issues' => $imports->issues(...),
        ];
    }

    public function handle(Request $request): Response
    {
        try {
            $this->matcher->getContext()->fromRequest($request);
            $match = $this->matcher->match($request->getPathInfo());
            $handler = $this->handlers[$match['_handler']] ?? null;

            return $handler
                ? $handler($request, $match)
                : new JsonResponse(['title' => 'Not found'], 404);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'title' => 'Internal Server Error',
                'detail' => $exception->getMessage(),
            ], 500);
        }
    }
}
