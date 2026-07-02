<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Tim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimController extends Controller
{
    /**
     * GET /api/v1/timovi
     * Filtriranje: ?naziv=sove, ?aktivan=1, ?sezona_id=1
     * Paginacija:  ?po_stranici=10&stranica=1
     * Sortiranje:  ?sort=naziv&smer=asc
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tim::query();

        // Filtriranje
        if ($request->filled('naziv')) {
            $query->where('naziv', 'like', "%{$request->naziv}%");
        }

        if ($request->has('aktivan')) {
            $query->where('aktivan', (bool) $request->aktivan);
        }

        // Filtriranje po sezoni – timovi koji su registrovani u sezoni
        if ($request->filled('sezona_id')) {
            $query->whereHas('sezone', fn($q) => $q->where('sezone.id', $request->sezona_id));
        }

        // Sortiranje
        $dozvoljenaPolja = ['naziv', 'created_at'];
        $sortPolje = in_array($request->sort, $dozvoljenaPolja) ? $request->sort : 'naziv';
        $sortSmer  = $request->smer === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortPolje, $sortSmer);

        // Paginacija
        $poStranici = min((int) $request->get('po_stranici', 10), 50);
        $rezultati  = $query->paginate($poStranici);

        return ApiResponse::uspesno([
            'podaci'     => $rezultati->items(),
            'paginacija' => $this->formatujPaginaciju($rezultati),
        ], 'Lista timova.');
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

    private function formatujPaginaciju($paginacija): array
    {
        return [
            'ukupno'        => $paginacija->total(),
            'po_stranici'   => $paginacija->perPage(),
            'stranica'      => $paginacija->currentPage(),
            'ukupno_strana' => $paginacija->lastPage(),
            'sledeca'       => $paginacija->nextPageUrl(),
            'prethodna'     => $paginacija->previousPageUrl(),
        ];
    }
}