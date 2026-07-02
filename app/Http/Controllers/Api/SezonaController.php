<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SezonaController extends Controller
{
    /**
     * GET /api/v1/sezone
     * Filtriranje: ?aktivna=1, ?naziv=2024
     * Paginacija: ?po_stranici=10&stranica=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sezona::query();

        // Filtriranje
        if ($request->has('aktivna')) {
            $query->where('aktivna', (bool) $request->aktivna);
        }

        if ($request->filled('naziv')) {
            $query->where('naziv', 'like', "%{$request->naziv}%");
        }

        if ($request->filled('od_datuma')) {
            $query->where('datum_pocetka', '>=', $request->od_datuma);
        }

        if ($request->filled('do_datuma')) {
            $query->where('datum_zavrsetka', '<=', $request->do_datuma);
        }

        // Sortiranje
        $dozvoljenaPolja = ['naziv', 'datum_pocetka', 'datum_zavrsetka', 'created_at'];
        $sortPolje = in_array($request->sort, $dozvoljenaPolja) ? $request->sort : 'datum_pocetka';
        $sortSmer  = $request->smer === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortPolje, $sortSmer);

        // Paginacija
        $poStranici = min((int) $request->get('po_stranici', 10), 50);
        $rezultati  = $query->paginate($poStranici);

        return ApiResponse::uspesno([
            'podaci'     => $rezultati->items(),
            'paginacija' => $this->formatujPaginaciju($rezultati),
        ], 'Lista sezona.');
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

    private function formatujPaginaciju($paginacija): array
    {
        return [
            'ukupno'       => $paginacija->total(),
            'po_stranici'  => $paginacija->perPage(),
            'stranica'     => $paginacija->currentPage(),
            'ukupno_strana'=> $paginacija->lastPage(),
            'sledeca'      => $paginacija->nextPageUrl(),
            'prethodna'    => $paginacija->previousPageUrl(),
        ];
    }
}