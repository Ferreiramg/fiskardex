<?php

declare(strict_types=1);

use Fiskardex\Application;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Application(dirname(__DIR__)))->handle(Request::createFromGlobals())->send();
