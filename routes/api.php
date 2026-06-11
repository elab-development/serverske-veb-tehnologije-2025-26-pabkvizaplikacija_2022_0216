<?php

use App\Http\Controllers\Api\DogadjajController;
use App\Http\Controllers\Api\RezultatController;
use App\Http\Controllers\Api\SezonaController;
use App\Http\Controllers\Api\TimController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Sezone ────────────────────────────────────────────────────────────────

    Route::get('sezone/aktivna', [SezonaController::class, 'aktivna']);
    Route::apiResource('sezone', SezonaController::class)
         ->parameters(['sezone' => 'sezona']);   // {sezone} → {sezona}
    Route::get('sezone/{sezona}/tabela-rezultata', [SezonaController::class, 'tabelaRezultata']);

    // ── Timovi ────────────────────────────────────────────────────────────────

    Route::apiResource('timovi', TimController::class)
         ->parameters(['timovi' => 'tim']);       // {timovi} → {tim}
    Route::post('timovi/{tim}/registracija/{sezona}', [TimController::class, 'registracijaZaSezonu']);
    Route::get('timovi/{tim}/statistike', [TimController::class, 'statistike']);

    // ── Dogadjaji ─────────────────────────────────────────────────────────────

    Route::get('dogadjaji/aktivni', [DogadjajController::class, 'aktivni']);

    Route::prefix('sezone/{sezona}/dogadjaji')->group(function () {
        Route::get('/',                    [DogadjajController::class, 'index']);
        Route::post('/',                   [DogadjajController::class, 'store']);
        Route::get('/{dogadjaj}',          [DogadjajController::class, 'show']);
        Route::put('/{dogadjaj}',          [DogadjajController::class, 'update']);
        Route::delete('/{dogadjaj}',       [DogadjajController::class, 'destroy']);
        Route::patch('/{dogadjaj}/status', [DogadjajController::class, 'promeniStatus']);

        Route::prefix('/{dogadjaj}/rezultati')->group(function () {
            Route::get('/',              [RezultatController::class, 'index']);
            Route::post('/',             [RezultatController::class, 'store']);
            Route::post('/batch',        [RezultatController::class, 'batch']);
            Route::get('/{rezultat}',    [RezultatController::class, 'show']);
            Route::put('/{rezultat}',    [RezultatController::class, 'update']);
            Route::delete('/{rezultat}', [RezultatController::class, 'destroy']);
        });
    });
});