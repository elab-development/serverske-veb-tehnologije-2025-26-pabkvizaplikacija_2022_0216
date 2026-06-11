<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Korisnik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AutentifikacijaController extends Controller
{
    /**
     * POST /api/v1/auth/registracija
     * Registracija novog korisnika.
     */
    public function registracija(Request $request): JsonResponse
    {
        $validirano = $request->validate([
            'ime'                  => 'required|string|max:100',
            'prezime'              => 'required|string|max:100',
            'email'                => 'required|email|unique:korisnici,email',
            'lozinka'              => ['required', 'confirmed', Password::min(8)
                                            ->letters()
                                            ->numbers()],
            'lozinka_confirmation' => 'required',
            'uloga'                => 'in:admin,sudija,gledalac',
        ]);

        $korisnik = Korisnik::create([
            'ime'     => $validirano['ime'],
            'prezime' => $validirano['prezime'],
            'email'   => $validirano['email'],
            'lozinka' => $validirano['lozinka'],   // $casts => 'hashed' automatski hashuje
            'uloga'   => $validirano['uloga'] ?? 'gledalac',
        ]);

        $token = $korisnik->createToken('api-token')->plainTextToken;

        return ApiResponse::kreirano([
            'korisnik' => $this->formatujKorisnika($korisnik),
            'token'    => $token,
            'tip'      => 'Bearer',
        ], 'Registracija uspešna. Dobrodošli!');
    }

    /**
     * POST /api/v1/auth/prijava
     * Prijava korisnika i izdavanje tokena.
     */
    public function prijava(Request $request): JsonResponse
    {
        $validirano = $request->validate([
            'email'   => 'required|email',
            'lozinka' => 'required|string',
        ]);

        $korisnik = Korisnik::where('email', $validirano['email'])->first();

        if (!$korisnik || !Hash::check($validirano['lozinka'], $korisnik->lozinka)) {
            return ApiResponse::greska('Email ili lozinka nisu ispravni.', 401);
        }

        // Obriši stare tokene i kreiraj novi
        $korisnik->tokens()->delete();
        $token = $korisnik->createToken('api-token')->plainTextToken;

        return ApiResponse::uspesno([
            'korisnik' => $this->formatujKorisnika($korisnik),
            'token'    => $token,
            'tip'      => 'Bearer',
        ], 'Prijava uspešna.');
    }

    /**
     * POST /api/v1/auth/odjava
     * Odjava – brisanje trenutnog tokena.
     * Zahteva: Authorization: Bearer {token}
     */
    public function odjava(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::uspesno(null, 'Uspešno ste se odjavili.');
    }

    /**
     * GET /api/v1/auth/ja
     * Vraća podatke trenutno prijavljenog korisnika.
     * Zahteva: Authorization: Bearer {token}
     */
    public function ja(Request $request): JsonResponse
    {
        return ApiResponse::uspesno(
            $this->formatujKorisnika($request->user()),
            'Podaci prijavljenog korisnika.'
        );
    }

    // ── Privatni helperi ──────────────────────────────────────────────────────

    private function formatujKorisnika(Korisnik $korisnik): array
    {
        return [
            'id'      => $korisnik->id,
            'ime'     => $korisnik->ime,
            'prezime' => $korisnik->prezime,
            'email'   => $korisnik->email,
            'uloga'   => $korisnik->uloga,
        ];
    }
}