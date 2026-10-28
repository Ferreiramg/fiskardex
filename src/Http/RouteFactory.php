<?php

declare(strict_types=1);

namespace Fiskardex\Http;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class RouteFactory
{
    public function create(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->add('home', new Route('/', ['_handler' => 'home']));
        $routes->add('health', new Route('/health', ['_handler' => 'health']));
        $routes->add(
            'imports.index',
            new Route('/api/v1/imports', ['_handler' => 'imports.index'], [], [], '', [], ['GET']),
        );
        $routes->add(
            'imports.create',
            new Route('/api/v1/imports', ['_handler' => 'imports.create'], [], [], '', [], ['POST']),
        );
        $routes->add(
            'imports.show',
            new Route(
                '/api/v1/imports/{id}',
                ['_handler' => 'imports.show'],
                ['id' => '[0-9a-f-]+'],
                [],
                '',
                [],
                ['GET'],
            ),
        );
        $routes->add(
            'imports.delete',
            new Route(
                '/api/v1/imports/{id}',
                ['_handler' => 'imports.delete'],
                ['id' => '[0-9a-f-]+'],
                [],
                '',
                [],
                ['DELETE'],
            ),
        );
        $routes->add(
            'imports.records',
            new Route('/api/v1/imports/{id}/records', ['_handler' => 'imports.records'], ['id' => '[0-9a-f-]+']),
        );
        $routes->add(
            'imports.documents',
            new Route('/api/v1/imports/{id}/documents', ['_handler' => 'imports.documents'], ['id' => '[0-9a-f-]+']),
        );
        $routes->add(
            'imports.issues',
            new Route('/api/v1/imports/{id}/issues', ['_handler' => 'imports.issues'], ['id' => '[0-9a-f-]+']),
        );

        return $routes;
    }
}
