<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SezonaController extends Controller
{
    public function index(): JsonResponse
    {
        $sezone = Sezona::orderByDesc('aktivna')
            ->orderByDesc('datum_pocetka')
            ->get();

        return ApiResponse::uspesno($sezone, 'Lista svih sezona.');
    }

    public function store(Request $request): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'           => 'required|string|max:255|unique:sezone,naziv',
            'slug'            => 'required|string|max:255|unique:sezone,slug',
            'datum_pocetka'   => 'required|date',
            'datum_zavrsetka' => 'required|date|after:datum_pocetka',
            'aktivna'         => 'boolean',
            'opis'            => 'nullable|string',
        ]);

        if (!empty($validirano['aktivna'])) {
            Sezona::where('aktivna', true)->update(['aktivna' => false]);
        }

        $sezona = Sezona::create($validirano);

        return ApiResponse::kreirano($sezona, 'Sezona je uspešno kreirana.');
    }

    public function show(Sezona $sezona): JsonResponse
    {
        $sezona->load(['dogadjaji' => fn($q) => $q->orderBy('datum_dogadjaja')]);

        return ApiResponse::uspesno($sezona, 'Detalji sezone.');
    }

    public function update(Request $request, Sezona $sezona): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'           => "sometimes|string|max:255|unique:sezone,naziv,{$sezona->id}",
            'slug'            => "sometimes|string|max:255|unique:sezone,slug,{$sezona->id}",
            'datum_pocetka'   => 'sometimes|date',
            'datum_zavrsetka' => 'sometimes|date|after:datum_pocetka',
            'aktivna'         => 'boolean',
            'opis'            => 'nullable|string',
        ]);

        if (!empty($validirano['aktivna'])) {
            Sezona::where('aktivna', true)->update(['aktivna' => false]);
        }

        $sezona->update($validirano);

        return ApiResponse::uspesno($sezona, 'Sezona je uspešno ažurirana.');
    }

    public function destroy(Sezona $sezona): JsonResponse
    {
        $sezona->delete();

        return ApiResponse::obrisano('Sezona je uspešno obrisana.');
    }

    public function tabelaRezultata(Sezona $sezona): JsonResponse
    {
        $tabela = $sezona->timovi()
            ->orderByPivot('ukupni_bodovi', 'desc')
            ->get()
            ->map(fn($tim, $index) => [
                'pozicija'           => $index + 1,
                'tim'                => $tim->only(['id', 'naziv', 'slug', 'logo_url']),
                'ukupni_bodovi'      => $tim->pivot->ukupni_bodovi,
                'odigrani_dogadjaji' => $tim->pivot->odigrani_dogadjaji,
            ]);

        return ApiResponse::uspesno([
            'sezona' => $sezona->only(['id', 'naziv', 'slug']),
            'tabela' => $tabela,
        ], 'Tabela rezultata za sezonu.');
    }

    public function aktivna(): JsonResponse
    {
        $sezona = Sezona::where('aktivna', true)->first();

        if (!$sezona) {
            return ApiResponse::nijePronađen('Nema aktivne sezone.');
        }

        return ApiResponse::uspesno($sezona, 'Aktivna sezona.');
    }
}