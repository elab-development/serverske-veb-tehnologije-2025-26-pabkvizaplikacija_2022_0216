<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponse;
use App\Models\Korisnik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class LozinkaController extends Controller
{
    public function posaljiKod(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:korisnici,email',
        ]);

        DB::table('reset_lozinke')->where('email', $request->email)->delete();

        $kod = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('reset_lozinke')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($kod),
            'created_at' => now(),
        ]);

        return ApiResponse::uspesno(['kod' => $kod], 'Kod je poslat na email.');
    }

    public function verifikujKod(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:korisnici,email',
            'kod'   => 'required|string|size:6',
        ]);

        $zapis = DB::table('reset_lozinke')
            ->where('email', $request->email)
            ->latest('created_at')
            ->first();

        if (!$zapis) {
            return ApiResponse::greska('Kod nije pronađen. Zatražite novi.', 404);
        }

        if (now()->diffInMinutes($zapis->created_at) > 60) {
            DB::table('reset_lozinke')->where('email', $request->email)->delete();
            return ApiResponse::greska('Kod je istekao. Zatražite novi.', 422);
        }

        if (!Hash::check($request->kod, $zapis->token)) {
            return ApiResponse::greska('Kod nije ispravan.', 422);
        }

        return ApiResponse::uspesno(null, 'Kod je ispravan. Unesite novu lozinku.');
    }

    public function resetuj(Request $request): JsonResponse
    {
        $request->validate([
            'email'                     => 'required|email|exists:korisnici,email',
            'kod'                       => 'required|string|size:6',
            'nova_lozinka'              => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'nova_lozinka_confirmation' => 'required',
        ]);

        $zapis = DB::table('reset_lozinke')
            ->where('email', $request->email)
            ->latest('created_at')
            ->first();

        if (!$zapis) {
            return ApiResponse::greska('Kod nije pronađen. Zatražite novi.', 404);
        }

        if (now()->diffInMinutes($zapis->created_at) > 60) {
            DB::table('reset_lozinke')->where('email', $request->email)->delete();
            return ApiResponse::greska('Kod je istekao. Zatražite novi.', 422);
        }

        if (!Hash::check($request->kod, $zapis->token)) {
            return ApiResponse::greska('Kod nije ispravan.', 422);
        }

        $korisnik = Korisnik::where('email', $request->email)->first();
        $korisnik->update(['lozinka' => $request->nova_lozinka]);
        $korisnik->tokens()->delete();
        DB::table('reset_lozinke')->where('email', $request->email)->delete();

        return ApiResponse::uspesno(null, 'Lozinka je uspešno promenjena. Prijavite se.');
    }

    public function promeni(Request $request): JsonResponse
    {
        $request->validate([
            'stara_lozinka'             => 'required|string',
            'nova_lozinka'              => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'nova_lozinka_confirmation' => 'required',
        ]);

        $korisnik = $request->user();

        if (!Hash::check($request->stara_lozinka, $korisnik->lozinka)) {
            return ApiResponse::greska('Stara lozinka nije ispravna.', 422);
        }

        $korisnik->update(['lozinka' => $request->nova_lozinka]);
        $korisnik->tokens()
            ->where('id', '!=', $request->user()->currentAccessToken()->id)
            ->delete();

        return ApiResponse::uspesno(null, 'Lozinka je uspešno promenjena.');
    }
}