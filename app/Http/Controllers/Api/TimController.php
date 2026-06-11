<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Tim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimController extends Controller
{
    public function index(): JsonResponse
    {
        $timovi = Tim::aktivan()->orderBy('naziv')->get();

        return ApiResponse::uspesno($timovi, 'Lista svih aktivnih timova.');
    }

    public function store(Request $request): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'         => 'required|string|max:255|unique:timovi,naziv',
            'slug'          => 'required|string|max:255|unique:timovi,slug',
            'kontakt_email' => 'required|email|unique:timovi,kontakt_email',
            'logo_url'      => 'nullable|url',
        ]);

        $tim = Tim::create($validirano);

        return ApiResponse::kreirano($tim, 'Tim je uspešno registrovan.');
    }

    public function show(Tim $tim): JsonResponse
    {
        $tim->load(['sezone' => fn($q) => $q->orderByDesc('datum_pocetka')]);

        return ApiResponse::uspesno($tim, 'Detalji tima.');
    }

    public function update(Request $request, Tim $tim): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'         => "sometimes|string|max:255|unique:timovi,naziv,{$tim->id}",
            'slug'          => "sometimes|string|max:255|unique:timovi,slug,{$tim->id}",
            'kontakt_email' => "sometimes|email|unique:timovi,kontakt_email,{$tim->id}",
            'logo_url'      => 'nullable|url',
            'aktivan'       => 'boolean',
        ]);

        $tim->update($validirano);

        return ApiResponse::uspesno($tim, 'Tim je uspešno ažuriran.');
    }

    public function destroy(Tim $tim): JsonResponse
    {
        $tim->delete();

        return ApiResponse::obrisano('Tim je uspešno obrisan.');
    }

    public function registracijaZaSezonu(Tim $tim, int $sezonaId): JsonResponse
    {
        $tim->sezone()->syncWithoutDetaching([
            $sezonaId => [
                'ukupni_bodovi'      => 0,
                'odigrani_dogadjaji' => 0,
            ],
        ]);

        return ApiResponse::uspesno(null, "Tim '{$tim->naziv}' je uspešno registrovan za sezonu.");
    }

    public function statistike(Tim $tim): JsonResponse
    {
        $statistike = $tim->sezone()
            ->orderByDesc('datum_pocetka')
            ->get()
            ->map(fn($sezona) => [
                'sezona'             => $sezona->only(['id', 'naziv', 'slug']),
                'ukupni_bodovi'      => $sezona->pivot->ukupni_bodovi,
                'rang'               => $sezona->pivot->rang,
                'odigrani_dogadjaji' => $sezona->pivot->odigrani_dogadjaji,
            ]);

        return ApiResponse::uspesno([
            'tim'        => $tim->only(['id', 'naziv', 'slug', 'logo_url']),
            'statistike' => $statistike,
        ], 'Statistike tima po sezonama.');
    }
}