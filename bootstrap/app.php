<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // API rute vraćaju JSON 401 umesto redirecta na login stranicu
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('api/*')) {
                return null;
            }
            return route('login');
        });

        // Registracija middleware aliasa za proveru uloge
        $middleware->alias([
            'uloga' => \App\Http\Middleware\ProveriUlogu::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
        );

        // 401 – Neautentifikovan
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'uspesno' => false,
                    'poruka'  => 'Niste prijavljeni. Potreban je Bearer token.',
                ], 401);
            }
        });

        // 404 – Model ili ruta nije pronađena
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $previous = $e->getPrevious();
                $poruka = $previous instanceof ModelNotFoundException
                    ? "Resurs '" . class_basename($previous->getModel()) . "' nije pronađen."
                    : 'Tražena ruta ne postoji.';

                return response()->json([
                    'uspesno' => false,
                    'poruka'  => $poruka,
                ], 404);
            }
        });

        // 422 – Validaciona greška
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'uspesno' => false,
                    'poruka'  => 'Podaci nisu validni. Proverite unete vrednosti.',
                    'greske'  => $e->errors(),
                ], 422);
            }
        });

        // 405 – Metoda nije dozvoljena
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'uspesno' => false,
                    'poruka'  => 'HTTP metoda nije dozvoljena za ovu rutu.',
                ], 405);
            }
        });

    })->create();