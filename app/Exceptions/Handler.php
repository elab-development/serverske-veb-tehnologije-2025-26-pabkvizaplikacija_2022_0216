<?php

namespace App\Exceptions;

use App\Http\Resources\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Greške koje se ne loguju.
     */
    protected $dontReport = [
        //
    ];

    /**
     * Greške koje se ne flešuju u sesiju.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Sve greške na /api/* rutama vraćamo kao JSON.
     */
    public function render($request, Throwable $e): mixed
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->renderApiGreska($request, $e);
        }

        return parent::render($request, $e);
    }

    private function renderApiGreska(Request $request, Throwable $e): JsonResponse
    {
        // 422 – Validaciona greška
        if ($e instanceof ValidationException) {
            return ApiResponse::validacionaGreska(
                $e->errors(),
                'Podaci nisu validni. Proverite unete vrednosti.'
            );
        }

        // 404 – Eloquent model nije pronađen (npr. Route Model Binding)
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return ApiResponse::nijePronađen("Resurs '{$model}' nije pronađen.");
        }

        // 404 – Ruta nije pronađena
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::nijePronađen('Tražena ruta ne postoji.');
        }

        // 405 – HTTP metoda nije dozvoljena
        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::greska(
                'HTTP metoda nije dozvoljena za ovu rutu.',
                405
            );
        }

        // 401 – Neautorizovan
        if ($e instanceof AuthenticationException) {
            return ApiResponse::neautorizovan('Morate biti prijavljeni da biste pristupili ovom resursu.');
        }

        // 403 – Zabranjen pristup
        if ($e instanceof AccessDeniedHttpException) {
            return ApiResponse::zabranjen('Nemate dozvolu za ovu akciju.');
        }

        // 500 – Sve ostale greške
        $poruka = app()->isProduction()
            ? 'Interna greška servera. Pokušajte ponovo.'
            : $e->getMessage();

        return ApiResponse::serverska($poruka);
    }
}