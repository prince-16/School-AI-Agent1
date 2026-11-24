<?php

use App\Http\Middleware\EnsureCurrentSchool;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\SetSchoolContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetSchoolContext::class,
        ]);

        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: SetSchoolContext::class,
        );

        $middleware->alias([
            'school' => EnsureCurrentSchool::class,
            'platform' => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
