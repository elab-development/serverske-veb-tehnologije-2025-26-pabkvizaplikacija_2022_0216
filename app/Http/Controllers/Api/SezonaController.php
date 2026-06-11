<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SezonaController extends Controller
{
    /**
     * GET /api/sezone
     * Lista svih sezona, aktivna sezona dolazi prva.
     */
    public function index(): JsonResponse
    {
        $sezone = Sezona::orderByDesc('aktivna')
            ->orderByDesc('datum_pocetka')
            ->get();

        return response()->json($sezone);
    }

    /**
     * POST /api/sezone
     * Kreiranje nove sezone.
     */
    public function store(Request $request): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'            => 'required|string|max:255|unique:sezone,naziv',
            'slug'             => 'required|string|max:255|unique:sezone,slug',
            'datum_pocetka'    => 'required|date',
            'datum_zavrsetka'  => 'required|date|after:datum_pocetka',
            'aktivna'          => 'boolean',
            'opis'             => 'nullable|string',
        ]);

        // Ako se nova sezona pravi aktivnom, deaktiviraj ostale
        if (!empty($validirano['aktivna'])) {
            Sezona::where('aktivna', true)->update(['aktivna' => false]);
        }

        $sezona = Sezona::create($validirano);

        return response()->json($sezona, Response::HTTP_CREATED);
    }

    /**
     * GET /api/sezone/{sezona}
     * Detalji jedne sezone sa statistikama.
     */
    public function show(Sezona $sezona): JsonResponse
    {
        $sezona->load(['dogadjaji' => fn($q) => $q->orderBy('datum_dogadjaja')]);

        return response()->json($sezona);
    }

    /**
     * PUT /api/sezone/{sezona}
     * Ažuriranje sezone.
     */
    public function update(Request $request, Sezona $sezona): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'            => "sometimes|string|max:255|unique:sezone,naziv,{$sezona->id}",
            'slug'             => "sometimes|string|max:255|unique:sezone,slug,{$sezona->id}",
            'datum_pocetka'    => 'sometimes|date',
            'datum_zavrsetka'  => 'sometimes|date|after:datum_pocetka',
            'aktivna'          => 'boolean',
            'opis'             => 'nullable|string',
        ]);

        if (!empty($validirano['aktivna'])) {
            Sezona::where('aktivna', true)->update(['aktivna' => false]);
        }

        $sezona->update($validirano);

        return response()->json($sezona);
    }

    /**
     * DELETE /api/sezone/{sezona}
     * Soft-delete sezone.
     */
    public function destroy(Sezona $sezona): JsonResponse
    {
        $sezona->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * GET /api/sezone/{sezona}/tabela-rezultata
     * Scoreboard – timovi sortirani po ukupnim bodovima u sezoni.
     */
    public function tabelaRezultata(Sezona $sezona): JsonResponse
    {
        $tabela = $sezona->timovi()
            ->orderByPivot('ukupni_bodovi', 'desc')
            ->get()
            ->map(fn($tim, $index) => [
                'pozicija'            => $index + 1,
                'tim'                 => $tim->only(['id', 'naziv', 'slug', 'logo_url']),
                'ukupni_bodovi'       => $tim->pivot->ukupni_bodovi,
                'odigrani_dogadjaji'  => $tim->pivot->odigrani_dogadjaji,
            ]);

        return response()->json([
            'sezona' => $sezona->only(['id', 'naziv', 'slug']),
            'tabela' => $tabela,
        ]);
    }

    /**
     * GET /api/sezone/aktivna
     * Vraća trenutno aktivnu sezonu.
     */
    public function aktivna(): JsonResponse
    {
        $sezona = Sezona::where('aktivna', true)->firstOrFail();

        return response()->json($sezona);
    }
}
