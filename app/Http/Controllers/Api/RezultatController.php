<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Dogadjaj;
use App\Models\RezultatDogadjaja;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RezultatController extends Controller
{
    public function index(Sezona $sezona, Dogadjaj $dogadjaj): JsonResponse
    {
        $rezultati = $dogadjaj->rezultati()
            ->with('tim:id,naziv,slug,logo_url')
            ->orderBy('rang')
            ->get();

        return ApiResponse::uspesno($rezultati, "Rezultati za događaj '{$dogadjaj->naziv}'.");
    }

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

        $rezultat->sinhronizujTabeluSezone();
        $rezultat->load('tim:id,naziv,slug');

        return ApiResponse::kreirano($rezultat, 'Rezultat je uspešno unet.');
    }

    public function show(Sezona $sezona, Dogadjaj $dogadjaj, RezultatDogadjaja $rezultat): JsonResponse
    {
        $rezultat->load('tim:id,naziv,slug,logo_url');

        return ApiResponse::uspesno($rezultat, 'Detalji rezultata.');
    }

    public function update(Request $request, Sezona $sezona, Dogadjaj $dogadjaj, RezultatDogadjaja $rezultat): JsonResponse
    {
        $validirano = $request->validate([
            'bodovi'   => 'sometimes|integer|min:0',
            'rang'     => 'nullable|integer|min:1',
            'napomena' => 'nullable|string|max:500',
        ]);

        $rezultat->update($validirano);
        $rezultat->sinhronizujTabeluSezone();

        return ApiResponse::uspesno(
            $rezultat->load('tim:id,naziv,slug'),
            'Rezultat je uspešno ažuriran.'
        );
    }

    public function destroy(Sezona $sezona, Dogadjaj $dogadjaj, RezultatDogadjaja $rezultat): JsonResponse
    {
        $rezultat->delete();
        $rezultat->sinhronizujTabeluSezone();

        return ApiResponse::obrisano('Rezultat je uspešno obrisan.');
    }

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

        return ApiResponse::kreirano($sacuvani, 'Svi rezultati su uspešno uneti.');
    }
}