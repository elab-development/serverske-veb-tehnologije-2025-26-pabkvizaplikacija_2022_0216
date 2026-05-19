<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

use App\Http\Controllers\Api\SeasonController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route as RouteFacade;

RouteFacade::prefix('api')->group(function(){
    RouteFacade::get('seasons', [SeasonController::class,'index']);
    RouteFacade::get('seasons/{season}', [SeasonController::class,'show']);

    RouteFacade::get('events/current', [EventController::class,'current']);
    RouteFacade::get('events/{event}', [EventController::class,'show']);
    RouteFacade::post('events/{event}/bodovi', [EventController::class,'azurirajBodove']);

    RouteFacade::get('teams', [TeamController::class,'index']);
    RouteFacade::get('teams/{team}', [TeamController::class,'show']);
    RouteFacade::post('teams', [TeamController::class,'store']);
});
