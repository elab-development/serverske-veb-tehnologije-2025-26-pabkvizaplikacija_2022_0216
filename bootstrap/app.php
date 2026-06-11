<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Sve greške na api/* rutama vraćamo kao JSON
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
        );

        // 404 – Model nije pronađen (Route Model Binding)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'uspesno' => false,
                    'poruka'  => "Resurs '{$model}' nije pronađen.",
                ], 404);
            }
        });

        // 404 – NotFoundHttpException (wrappuje ModelNotFoundException u novijim verzijama)
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