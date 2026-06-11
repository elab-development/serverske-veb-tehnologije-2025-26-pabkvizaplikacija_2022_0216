<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dogadjaj;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DogadjajController extends Controller
{
    /**
     * GET /api/sezone/{sezona}/dogadjaji
     * Lista svih događaja u sezoni.
     */
    public function index(Sezona $sezona): JsonResponse
    {
        $dogadjaji = $sezona->dogadjaji()
            ->orderBy('datum_dogadjaja')
            ->get();

        return response()->json($dogadjaji);
    }

    /**
     * POST /api/sezone/{sezona}/dogadjaji
     * Kreiranje novog događaja u sezoni.
     */
    public function store(Request $request, Sezona $sezona): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'           => 'required|string|max:255',
            'slug'            => 'required|string|max:255|unique:dogadjaji,slug',
            'datum_dogadjaja' => 'required|date',
            'lokacija'        => 'nullable|string|max:255',
            'detaljan_opis'   => 'nullable|string',
            'max_timova'      => 'integer|min:2|max:100',
            'broj_rundi'      => 'integer|min:1|max:20',
        ]);

        $dogadjaj = $sezona->dogadjaji()->create($validirano);

        return response()->json($dogadjaj, Response::HTTP_CREATED);
    }

    /**
     * GET /api/sezone/{sezona}/dogadjaji/{dogadjaj}
     * Detalji događaja sa svim rezultatima.
     */
    public function show(Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $dogadjaj->load(['rezultati.tim']);

        return response()->json($dogadjaj);
    }

    /**
     * PUT /api/sezone/{sezona}/dogadjaji/{dogadjaj}
     * Ažuriranje događaja.
     */
    public function update(Request $request, Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $validirano = $request->validate([
            'naziv'           => 'sometimes|string|max:255',
            'slug'            => "sometimes|string|max:255|unique:dogadjaji,slug,{$dogadjaj->id}",
            'datum_dogadjaja' => 'sometimes|date',
            'lokacija'        => 'nullable|string|max:255',
            'detaljan_opis'   => 'nullable|string',
            'status'          => 'in:nadolazeci,u_toku,zavrsen',
            'max_timova'      => 'integer|min:2|max:100',
            'broj_rundi'      => 'integer|min:1|max:20',
        ]);

        $dogadjaj->update($validirano);

        return response()->json($dogadjaj);
    }

    /**
     * DELETE /api/sezone/{sezona}/dogadjaji/{dogadjaj}
     * Soft-delete događaja.
     */
    public function destroy(Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $dogadjaj->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * GET /api/dogadjaji/aktivni
     * Svi aktivni događaji (nadolazeci + u_toku) kroz sve sezone.
     */
    public function aktivni(): JsonResponse
    {
        $dogadjaji = Dogadjaj::aktivan()
            ->with('sezona:id,naziv,slug')
            ->orderBy('datum_dogadjaja')
            ->get();

        return response()->json($dogadjaji);
    }

    /**
     * PATCH /api/sezone/{sezona}/dogadjaji/{dogadjaj}/status
     * Promena statusa događaja (npr. nadolazeci → u_toku → zavrsen).
     */
    public function promeniStatus(Request $request, Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $validirano = $request->validate([
            'status' => 'required|in:nadolazeci,u_toku,zavrsen',
        ]);

        $dogadjaj->update(['status' => $validirano['status']]);

        return response()->json($dogadjaj);
    }

    // ── Privatni helperi ──────────────────────────────────────────────────────

    private function provjeriPripadnostSezoni(Sezona $sezona, Dogadjaj $dogadjaj): void
    {
        abort_if(
            $dogadjaj->sezona_id !== $sezona->id,
            Response::HTTP_NOT_FOUND,
            'Događaj ne pripada ovoj sezoni.'
        );
    }
}