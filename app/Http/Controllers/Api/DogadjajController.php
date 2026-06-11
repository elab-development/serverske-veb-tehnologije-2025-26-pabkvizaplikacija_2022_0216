<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Dogadjaj;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DogadjajController extends Controller
{
    public function index(Sezona $sezona): JsonResponse
    {
        $dogadjaji = $sezona->dogadjaji()
            ->orderBy('datum_dogadjaja')
            ->get();

        return ApiResponse::uspesno($dogadjaji, "Događaji u sezoni '{$sezona->naziv}'.");
    }

    public function store(Request $request, Sezona $sezona): JsonResponse
    {
        $validirano = $request->validate([
            'naziv'           => 'required|string|max:255',
            'slug'            => 'required|string|max:255|unique:dogadjaji,slug',
            'datum_dogadjaja' => 'required|date',
            'detaljan_opis'   => 'nullable|string',
            'max_timova'      => 'integer|min:2|max:100',
            'broj_rundi'      => 'integer|min:1|max:20',
        ]);

        $dogadjaj = $sezona->dogadjaji()->create($validirano);

        return ApiResponse::kreirano($dogadjaj, 'Događaj je uspešno kreiran.');
    }

    public function show(Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $dogadjaj->load(['rezultati.tim']);

        return ApiResponse::uspesno($dogadjaj, 'Detalji događaja.');
    }

    public function update(Request $request, Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $validirano = $request->validate([
            'naziv'           => 'sometimes|string|max:255',
            'slug'            => "sometimes|string|max:255|unique:dogadjaji,slug,{$dogadjaj->id}",
            'datum_dogadjaja' => 'sometimes|date',
            'detaljan_opis'   => 'nullable|string',
            'status'          => 'in:nadolazeci,u_toku,zavrsen',
            'max_timova'      => 'integer|min:2|max:100',
            'broj_rundi'      => 'integer|min:1|max:20',
        ]);

        $dogadjaj->update($validirano);

        return ApiResponse::uspesno($dogadjaj, 'Događaj je uspešno ažuriran.');
    }

    public function destroy(Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $dogadjaj->delete();

        return ApiResponse::obrisano('Događaj je uspešno obrisan.');
    }

    public function aktivni(): JsonResponse
    {
        $dogadjaji = Dogadjaj::aktivan()
            ->with('sezona:id,naziv,slug')
            ->orderBy('datum_dogadjaja')
            ->get();

        return ApiResponse::uspesno($dogadjaji, 'Svi aktivni događaji.');
    }

    public function promeniStatus(Request $request, Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $this->provjeriPripadnostSezoni($sezona, $dogadjaj);

        $validirano = $request->validate([
            'status' => 'required|in:nadolazeci,u_toku,zavrsen',
        ]);

        $dogadjaj->update(['status' => $validirano['status']]);

        return ApiResponse::uspesno($dogadjaj, "Status događaja promenjen u '{$validirano['status']}'.");
    }

    private function provjeriPripadnostSezoni(Sezona $sezona, Dogadjaj $dogadjaj): void
    {
        if ($dogadjaj->sezona_id !== $sezona->id) {
            abort(response()->json([
                'uspesno' => false,
                'poruka'  => 'Događaj ne pripada ovoj sezoni.',
            ], 404));
        }
    }
}