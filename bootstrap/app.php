<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {

            Route::prefix('api/client')
                ->middleware(['api', 'set.locale'])
                ->group(base_path('routes/api/client.php'));

            Route::prefix('api/driver')
                ->middleware(['api', 'set.locale'])
                ->group(base_path('routes/api/driver.php'));

            Route::prefix('api/general')
                ->middleware(['api', 'set.locale'])
                ->group(base_path('routes/api/general.php'));

            Route::middleware(['web'])
                ->group(base_path('routes/web.php'));
        },

        commands: __DIR__ . '/../routes/console.php',
        health: '/up'
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'client'     => \App\Http\Middleware\ClientMiddleware::class,
            'user.type'  => \App\Http\Middleware\SetUserTypeMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            if ($request->is('admin/*') || str_contains($request->path(), 'filament')) {
                return false;
            }

            return $request->expectsJson();
        });

        $exceptions->render(function (Throwable $e, Request $request) {

            if ($request->is('api/*')) {

                if ($e instanceof AuthenticationException) {
                    return json(__('Unauthenticated'), status: 'fail', headerStatus: 401);
                }

                if (
                    $e instanceof NotFoundHttpException ||
                    $e->getCode() == 404 ||
                    $e instanceof \Illuminate\Routing\Exceptions\BackedEnumCaseNotFoundException ||
                    $e instanceof MethodNotAllowedHttpException
                ) {
                    return json(__('Resource not found'), status: 'fail', headerStatus: 404);
                }

                if ($e->getCode() == 403) {
                    return json(__('User not authorized'), status: 'fail', headerStatus: 403);
                }

                if ($e->getCode() == 422) {
                    return json($e->getMessage(), status: 'fail', headerStatus: 422);
                }

                if ($e->getCode() == 301) {
                    return json($e->getMessage(), status: 'fail', headerStatus: 301);
                }

                if ($e instanceof BindingResolutionException) {
                    return json($e->getMessage(), status: 'fail', headerStatus: 500);
                }

                return json($e->getMessage() ?: __('Server Error'), status: 'fail', headerStatus: 500);
            }
            return null;
        });
    })
    ->create();
