<?php

namespace App\Http\Middleware;

use App\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProveriUlogu
{
    /**
     * Proverava da li prijavljeni korisnik ima jednu od dozvoljenih uloga.
     *
     * Korišćenje u rutama:
     *   ->middleware('uloga:admin')
     *   ->middleware('uloga:admin,sudija')
     */
    public function handle(Request $request, Closure $next, string ...$uloge): Response
    {
        $korisnik = $request->user();

        if (!$korisnik) {
            return response()->json(
                ApiResponse::greska('Niste prijavljeni.', 401)->getData(true),
                401
            );
        }

        if (!in_array($korisnik->uloga, $uloge)) {
            return response()->json(
                ApiResponse::greska(
                    "Nemate dozvolu za ovu akciju. Potrebna uloga: " . implode(' ili ', $uloge) . ".",
                    403
                )->getData(true),
                403
            );
        }

        return $next($request);
    }
}