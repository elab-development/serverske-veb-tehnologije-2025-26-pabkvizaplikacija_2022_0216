<?php

use App\Http\Controllers\Api\AutentifikacijaController;
use App\Http\Controllers\Api\DogadjajController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\LozinkaController;
use App\Http\Controllers\Api\RezultatController;
use App\Http\Controllers\Api\SezonaController;
use App\Http\Controllers\Api\TimController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ════════════════════════════════════════════════════════════════
    // AUTENTIFIKACIJA – javne rute
    // ════════════════════════════════════════════════════════════════

    Route::prefix('auth')->group(function () {
        Route::post('registracija', [AutentifikacijaController::class, 'registracija']);
        Route::post('prijava',      [AutentifikacijaController::class, 'prijava']);
    });

    // ════════════════════════════════════════════════════════════════
    // ZABORAVLJENA LOZINKA – javne rute
    // ════════════════════════════════════════════════════════════════

    Route::prefix('lozinka')->group(function () {
        Route::post('zaboravljena',  [LozinkaController::class, 'posaljiKod']);
        Route::post('verifikuj-kod', [LozinkaController::class, 'verifikujKod']);
        Route::post('resetuj',       [LozinkaController::class, 'resetuj']);
    });

    // ════════════════════════════════════════════════════════════════
    // JAVNE READ RUTE – bez tokena
    // ════════════════════════════════════════════════════════════════

    Route::get('sezone/aktivna',                      [SezonaController::class, 'aktivna']);
    Route::get('sezone',                              [SezonaController::class, 'index']);
    Route::get('sezone/{sezona}',                     [SezonaController::class, 'show']);
    Route::get('sezone/{sezona}/tabela-rezultata',    [SezonaController::class, 'tabelaRezultata']);

    Route::get('timovi',                              [TimController::class, 'index']);
    Route::get('timovi/{tim}',                        [TimController::class, 'show']);
    Route::get('timovi/{tim}/statistike',             [TimController::class, 'statistike']);

    Route::get('dogadjaji/aktivni',                   [DogadjajController::class, 'aktivni']);
    Route::get('sezone/{sezona}/dogadjaji',           [DogadjajController::class, 'index']);
    Route::get('sezone/{sezona}/dogadjaji/{dogadjaj}',[DogadjajController::class, 'show']);

    Route::get('sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati',            [RezultatController::class, 'index']);
    Route::get('sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/{rezultat}', [RezultatController::class, 'show']);

    // ════════════════════════════════════════════════════════════════
    // ZAŠTIĆENE RUTE – Bearer token obavezan
    // ════════════════════════════════════════════════════════════════

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('odjava', [AutentifikacijaController::class, 'odjava']);
            Route::get('ja',      [AutentifikacijaController::class, 'ja']);
        });

        // Lozinka
        Route::post('lozinka/promeni', [LozinkaController::class, 'promeni']);

        // Sezone
        Route::post('sezone',            [SezonaController::class, 'store']);
        Route::put('sezone/{sezona}',    [SezonaController::class, 'update']);
        Route::delete('sezone/{sezona}', [SezonaController::class, 'destroy']);

        // Timovi
        Route::post('timovi',            [TimController::class, 'store']);
        Route::put('timovi/{tim}',       [TimController::class, 'update']);
        Route::delete('timovi/{tim}',    [TimController::class, 'destroy']);
        Route::post('timovi/{tim}/registracija/{sezona}', [TimController::class, 'registracijaZaSezonu']);

        // Dogadjaji
        Route::post('sezone/{sezona}/dogadjaji',                    [DogadjajController::class, 'store']);
        Route::put('sezone/{sezona}/dogadjaji/{dogadjaj}',          [DogadjajController::class, 'update']);
        Route::delete('sezone/{sezona}/dogadjaji/{dogadjaj}',       [DogadjajController::class, 'destroy']);
        Route::patch('sezone/{sezona}/dogadjaji/{dogadjaj}/status', [DogadjajController::class, 'promeniStatus']);

        // Rezultati
        Route::post('sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati',              [RezultatController::class, 'store']);
        Route::post('sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/batch',        [RezultatController::class, 'batch']);
        Route::put('sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/{rezultat}',    [RezultatController::class, 'update']);
        Route::delete('sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/{rezultat}', [RezultatController::class, 'destroy']);

        // ── Export CSV ────────────────────────────────────────────────────────
        Route::prefix('export')->group(function () {
            Route::get('timovi',                                         [ExportController::class, 'timovi']);
            Route::get('sezone/{sezona}/tabela-rezultata',               [ExportController::class, 'tabelaRezultata']);
            Route::get('sezone/{sezona}/dogadjaji',                      [ExportController::class, 'dogadjaji']);
            Route::get('sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati', [ExportController::class, 'rezultatiDogadjaja']);
        });
    });
});