<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TimController extends Controller
{
    /**
     * GET /api/timovi
     * Lista svih aktivnih timova.
     */
    public function index(): JsonResponse
    {
        $timovi = Tim::aktivan()->orderBy('naziv')->get();

        return response()->json($timovi);
    }

    /**
     * POST /api/timovi
     * Registracija novog tima.
     */
    public function store(Request $request): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'           => 'required|string|max:255|unique:timovi,naziv',
            'slug'            => 'required|string|max:255|unique:timovi,slug',
            'kontakt_email'   => 'required|email|unique:timovi,kontakt_email',
            'logo_url'        => 'nullable|url',
        ]);

        $tim = Tim::create($validirano);

        return response()->json($tim, Response::HTTP_CREATED);
    }

    /**
     * GET /api/timovi/{tim}
     * Detalji tima sa svim sezonama u kojima je učestvovao.
     */
    public function show(Tim $tim): JsonResponse
    {
        $tim->load(['sezone' => fn($q) => $q->orderByDesc('datum_pocetka')]);

        return response()->json($tim);
    }

    /**
     * PUT /api/timovi/{tim}
     * Ažuriranje podataka o timu.
     */
    public function update(Request $request, Tim $tim): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'           => "sometimes|string|max:255|unique:timovi,naziv,{$tim->id}",
            'slug'            => "sometimes|string|max:255|unique:timovi,slug,{$tim->id}",
            'kontakt_email'   => "sometimes|email|unique:timovi,kontakt_email,{$tim->id}",
            'logo_url'        => 'nullable|url',
            'aktivan'         => 'boolean',
        ]);

        $tim->update($validirano);

        return response()->json($tim);
    }

    /**
     * DELETE /api/timovi/{tim}
     * Soft-delete tima.
     */
    public function destroy(Tim $tim): JsonResponse
    {
        $tim->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * POST /api/timovi/{tim}/registracija/{sezona}
     * Registracija tima za određenu sezonu.
     */
    public function registracijaZaSezonu(Tim $tim, int $sezonaId): JsonResponse
    {
        $tim->sezone()->syncWithoutDetaching([
            $sezonaId => [
                'ukupni_bodovi'      => 0,
                'odigrani_dogadjaji' => 0,
            ],
        ]);

        return response()->json([
            'poruka' => "Tim '{$tim->naziv}' uspešno registrovan za sezonu.",
        ]);
    }

    /**
     * GET /api/timovi/{tim}/statistike
     * Statistike tima po sezonama (bodovi, rang, broj nastupa).
     */
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

        return response()->json([
            'tim'        => $tim->only(['id', 'naziv', 'slug', 'logo_url']),
            'statistike' => $statistike,
        ]);
    }
}