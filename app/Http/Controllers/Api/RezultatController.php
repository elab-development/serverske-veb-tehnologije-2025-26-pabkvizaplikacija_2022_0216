<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dogadjaj;
use App\Models\RezultatDogadjaja;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RezultatController extends Controller
{
    /**
     * GET /api/sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati
     * Lista svih rezultata za događaj, sortirana po rangu.
     */
    public function index(Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $rezultati = $dogadjaj->rezultati()
            ->with('tim:id,naziv,slug,logo_url')
            ->orderBy('rang')
            ->get();

        return response()->json($rezultati);
    }

    /**
     * POST /api/sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati
     * Unos rezultata za jedan tim na događaju.
     */
    public function store(Request $request, Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $validirano = $request->validate([
            'tim_id'   => 'required|exists:timovi,id',
            'bodovi'   => 'required|integer|min:0',
            'rang'     => 'nullable|integer|min:1',
            'napomena' => 'nullable|string|max:500',
        ]);

        $rezultat = RezultatDogadjaja::updateOrCreate(
            ['dogadjaj_id' => $dogadjaj->id, 'tim_id' => $validirano['tim_id']],
            $validirano + ['dogadjaj_id' => $dogadjaj->id]
        );

        // Ažuriraj scoreboard sezone
        $rezultat->sinhronizujTabeluSezone();

        return response()->json(
            $rezultat->load('tim:id,naziv,slug'),
            Response::HTTP_CREATED
        );
    }

    /**
     * GET /api/sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/{rezultat}
     * Detalji jednog rezultata.
     */
    public function show(Sezona $sezona, Dogadjaj $dogadjaj, RezultatDogadjaja $rezultat): JsonResponse
    {
        $rezultat->load('tim:id,naziv,slug,logo_url');

        return response()->json($rezultat);
    }

    /**
     * PUT /api/sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/{rezultat}
     * Izmena rezultata (npr. korekcija bodova).
     */
    public function update(
        Request $request,
        Sezona $sezona,
        Dogadjaj $dogadjaj,
        RezultatDogadjaja $rezultat
    ): JsonResponse {
        $validirano = $request->validate([
            'bodovi'   => 'sometimes|integer|min:0',
            'rang'     => 'nullable|integer|min:1',
            'napomena' => 'nullable|string|max:500',
        ]);

        $rezultat->update($validirano);

        // Ponovo sinhronizuj scoreboard nakon korekcije
        $rezultat->sinhronizujTabeluSezone();

        return response()->json($rezultat->load('tim:id,naziv,slug'));
    }

    /**
     * DELETE /api/sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/{rezultat}
     * Brisanje rezultata.
     */
    public function destroy(
        Sezona $sezona,
        Dogadjaj $dogadjaj,
        RezultatDogadjaja $rezultat
    ): JsonResponse {
        $rezultat->delete();

        // Sinhronizuj scoreboard nakon brisanja
        $rezultat->sinhronizujTabeluSezone();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * POST /api/sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati/batch
     * Masovni unos rezultata za sve timove odjednom.
     */
    public function batch(Request $request, Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $validirano = $request->validate([
            'rezultati'            => 'required|array|min:1',
            'rezultati.*.tim_id'   => 'required|exists:timovi,id',
            'rezultati.*.bodovi'   => 'required|integer|min:0',
            'rezultati.*.rang'     => 'nullable|integer|min:1',
            'rezultati.*.napomena' => 'nullable|string|max:500',
        ]);

        $sacuvani = collect($validirano['rezultati'])
            ->map(function ($stavka) use ($dogadjaj) {
                $rezultat = RezultatDogadjaja::updateOrCreate(
                    ['dogadjaj_id' => $dogadjaj->id, 'tim_id' => $stavka['tim_id']],
                    $stavka + ['dogadjaj_id' => $dogadjaj->id]
                );
                $rezultat->sinhronizujTabeluSezone();
                $rezultat->load('tim:id,naziv,slug');
                return $rezultat;
            });

        return response()->json($sacuvani, Response::HTTP_CREATED);
    }
}