<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Use default web middleware group
        $middleware->web(append: [
            // Add your custom middleware here if needed
        ]);

        // Custom middleware aliases
        $middleware->alias([
            'auth.frontend' => \App\Http\Middleware\FrontendAuthMiddleware::class,
            'admin.frontend' => \App\Http\Middleware\FrontendAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();