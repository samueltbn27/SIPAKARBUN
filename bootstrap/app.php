<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware): void {
        // Allow the first-party Blade application to authenticate its
        // same-origin API requests through the existing web session guard.
        // Route authorization remains enforced by auth:sanctum and Spatie
        // role middleware on each API route.
        $middleware->statefulApi();

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Cegah browser caching HTML agar perubahan Blade langsung
        // terlihat tanpa perlu hard-refresh.
        $middleware->append(\App\Http\Middleware\NoCacheHtml::class);

        // Guest middleware (RedirectIfAuthenticated) mengarahkan user
        // yang SUDAH login ke dashboard, BUKAN ke "/" (yang akan loop
        // balik ke /login).
        $middleware->redirectUsersTo(fn () => route('knowledge.dashboard'));

        // Auth middleware mengarahkan user yang BELUM login ke halaman login.
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
