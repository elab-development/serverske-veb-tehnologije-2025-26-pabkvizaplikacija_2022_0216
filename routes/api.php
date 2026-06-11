<?php

use App\Http\Controllers\Api\AutentifikacijaController;
use App\Http\Controllers\Api\DogadjajController;
use App\Http\Controllers\Api\RezultatController;
use App\Http\Controllers\Api\SezonaController;
use App\Http\Controllers\Api\TimController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ════════════════════════════════════════════════════════════════
    // AUTENTIFIKACIJA (javne rute – ne zahtevaju token)
    // ════════════════════════════════════════════════════════════════

    Route::prefix('auth')->group(function () {
        Route::post('registracija', [AutentifikacijaController::class, 'registracija']);
        Route::post('prijava',      [AutentifikacijaController::class, 'prijava']);
    });

    // ════════════════════════════════════════════════════════════════
    // ZAŠTIĆENE RUTE (zahtevaju: Authorization: Bearer {token})
    // ════════════════════════════════════════════════════════════════

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('odjava', [AutentifikacijaController::class, 'odjava']);
            Route::get('ja',      [AutentifikacijaController::class, 'ja']);
        });

        // ── 1. Resource rute ──────────────────────────────────────────────────

        Route::apiResource('sezone', SezonaController::class)
             ->parameters(['sezone' => 'sezona']);

        Route::apiResource('timovi', TimController::class)
             ->parameters(['timovi' => 'tim']);

        // ── 2. Statičke rute ──────────────────────────────────────────────────

        Route::get('sezone/aktivna',    [SezonaController::class, 'aktivna']);
        Route::get('dogadjaji/aktivni', [DogadjajController::class, 'aktivni']);

        // ── 3. Nested rute ────────────────────────────────────────────────────

        Route::prefix('sezone/{sezona}')->group(function () {

            Route::get('tabela-rezultata', [SezonaController::class, 'tabelaRezultata']);

            Route::prefix('dogadjaji')->group(function () {
                Route::get('/',                    [DogadjajController::class, 'index']);
                Route::post('/',                   [DogadjajController::class, 'store']);
                Route::get('/{dogadjaj}',          [DogadjajController::class, 'show']);
                Route::put('/{dogadjaj}',          [DogadjajController::class, 'update']);
                Route::delete('/{dogadjaj}',       [DogadjajController::class, 'destroy']);

                Route::prefix('/{dogadjaj}/rezultati')->group(function () {
                    Route::get('/',              [RezultatController::class, 'index']);
                    Route::post('/',             [RezultatController::class, 'store']);
                    Route::get('/{rezultat}',    [RezultatController::class, 'show']);
                    Route::put('/{rezultat}',    [RezultatController::class, 'update']);
                    Route::delete('/{rezultat}', [RezultatController::class, 'destroy']);
                    Route::post('/batch',        [RezultatController::class, 'batch']);
                });
            });
        });

        // ── 4. Akcijske rute ──────────────────────────────────────────────────

        Route::patch(
            'sezone/{sezona}/dogadjaji/{dogadjaj}/status',
            [DogadjajController::class, 'promeniStatus']
        );

        Route::post(
            'timovi/{tim}/registracija/{sezona}',
            [TimController::class, 'registracijaZaSezonu']
        );

        Route::get(
            'timovi/{tim}/statistike',
            [TimController::class, 'statistike']
        );

    });

});