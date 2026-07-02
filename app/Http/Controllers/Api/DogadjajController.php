<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Dogadjaj;
use App\Models\Sezona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DogadjajController extends Controller
{
    /**
     * GET /api/v1/sezone/{sezona}/dogadjaji
     * Filtriranje: ?status=nadolazeci, ?naziv=kviz, ?od_datuma=2025-01-01
     * Paginacija:  ?po_stranici=10&stranica=1
     * Sortiranje:  ?sort=datum_dogadjaja&smer=asc
     */
    public function index(Request $request, Sezona $sezona): JsonResponse
    {
        $query = $sezona->dogadjaji();

        // Filtriranje
        if ($request->filled('status')) {
            $dozvoljeniStatusi = ['nadolazeci', 'u_toku', 'zavrsen'];
            if (in_array($request->status, $dozvoljeniStatusi)) {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('naziv')) {
            $query->where('naziv', 'like', "%{$request->naziv}%");
        }

        if ($request->filled('od_datuma')) {
            $query->where('datum_dogadjaja', '>=', $request->od_datuma);
        }

        if ($request->filled('do_datuma')) {
            $query->where('datum_dogadjaja', '<=', $request->do_datuma);
        }

        // Sortiranje
        $dozvoljenaPolja = ['naziv', 'datum_dogadjaja', 'status', 'created_at'];
        $sortPolje = in_array($request->sort, $dozvoljenaPolja) ? $request->sort : 'datum_dogadjaja';
        $sortSmer  = $request->smer === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortPolje, $sortSmer);

        // Paginacija
        $poStranici = min((int) $request->get('po_stranici', 10), 50);
        $rezultati  = $query->paginate($poStranici);

        return ApiResponse::uspesno([
            'podaci'     => $rezultati->items(),
            'paginacija' => $this->formatujPaginaciju($rezultati),
        ], "Događaji u sezoni '{$sezona->naziv}'.");
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

    public function aktivni(Request $request): JsonResponse
    {
        $query = Dogadjaj::aktivan()->with('sezona:id,naziv,slug');

        if ($request->filled('sezona_id')) {
            $query->where('sezona_id', $request->sezona_id);
        }

        $poStranici = min((int) $request->get('po_stranici', 10), 50);
        $rezultati  = $query->orderBy('datum_dogadjaja')->paginate($poStranici);

        return ApiResponse::uspesno([
            'podaci'     => $rezultati->items(),
            'paginacija' => $this->formatujPaginaciju($rezultati),
        ], 'Svi aktivni događaji.');
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