<?php

use App\Http\Controllers\Api\DogadjajController;
use App\Http\Controllers\Api\RezultatController;
use App\Http\Controllers\Api\SezonaController;
use App\Http\Controllers\Api\TimController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::apiResource('sezone', SezonaController::class)
         ->parameters(['sezone' => 'sezona']);

    Route::apiResource('timovi', TimController::class)
         ->parameters(['timovi' => 'tim']);



    // 2. STATIČKE RUTE (named endpoints bez parametara)

    Route::get('sezone/aktivna',     [SezonaController::class, 'aktivna']);
    Route::get('dogadjaji/aktivni',  [DogadjajController::class, 'aktivni']);


    // 3. NESTED RUTE (hijerarhijski resursi)

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


    // 4. AKCIJSKE RUTE (custom akcije nad resursom)


    // Promena statusa događaja: nadolazeci → u_toku → zavrsen
    Route::patch(
        'sezone/{sezona}/dogadjaji/{dogadjaj}/status',
        [DogadjajController::class, 'promeniStatus']
    );

    // Prijava tima za sezonu
    Route::post(
        'timovi/{tim}/registracija/{sezona}',
        [TimController::class, 'registracijaZaSezonu']
    );

    // Statistike tima po svim sezonama
    Route::get(
        'timovi/{tim}/statistike',
        [TimController::class, 'statistike']
    );

});