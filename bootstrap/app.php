<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // L'espace Client est une SPA sans route web nommee "login" :
        // les requetes API sans header JSON doivent rester en 401 JSON
        // (inchange), et ne jamais planter sur route('login') inexistante.
        $middleware->redirectGuestsTo(fn () => url('/'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
