<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Illuminate\Foundation\Configuration\Middleware $middleware) {
        // alias lain bawaan Laravel / Breeze...
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            // alias lain...
            'resident.only' => \App\Http\Middleware\EnsureUserIsResident::class,
        ]);

    })->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
