<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dogadjaj;
use App\Models\Sezona;
use App\Models\Tim;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * GET /api/v1/export/timovi
     * Exportuje listu svih timova u CSV format.
     */
    public function timovi(): StreamedResponse
    {
        $timovi = Tim::aktivan()->orderBy('naziv')->get();

        return response()->streamDownload(function () use ($timovi) {
            $fajl = fopen('php://output', 'w');

            // UTF-8 BOM za ispravan prikaz u Excelu
            fputs($fajl, "\xEF\xBB\xBF");

            // Zaglavlje
            fputcsv($fajl, ['ID', 'Naziv', 'Email', 'Aktivan', 'Datum registracije']);

            // Podaci
            foreach ($timovi as $tim) {
                fputcsv($fajl, [
                    $tim->id,
                    $tim->naziv,
                    $tim->kontakt_email,
                    $tim->aktivan ? 'Da' : 'Ne',
                    $tim->created_at->format('d.m.Y'),
                ]);
            }

            fclose($fajl);
        }, 'timovi.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * GET /api/v1/export/sezone/{sezona}/tabela-rezultata
     * Exportuje tabelu rezultata sezone u CSV format.
     */
    public function tabelaRezultata(Sezona $sezona): StreamedResponse
    {
        $timovi = $sezona->timovi()
            ->orderByPivot('ukupni_bodovi', 'desc')
            ->get();

        return response()->streamDownload(function () use ($sezona, $timovi) {
            $fajl = fopen('php://output', 'w');

            fputs($fajl, "\xEF\xBB\xBF");

            // Info o sezoni
            fputcsv($fajl, ['Sezona:', $sezona->naziv]);
            fputcsv($fajl, ['Period:', $sezona->datum_pocetka . ' - ' . $sezona->datum_zavrsetka]);
            fputcsv($fajl, []);

            // Zaglavlje tabele
            fputcsv($fajl, ['Pozicija', 'Tim', 'Ukupni bodovi', 'Odigrani dogadjaji']);

            // Podaci
            foreach ($timovi as $index => $tim) {
                fputcsv($fajl, [
                    $index + 1,
                    $tim->naziv,
                    $tim->pivot->ukupni_bodovi,
                    $tim->pivot->odigrani_dogadjaji,
                ]);
            }

            fclose($fajl);
        }, "tabela-rezultata-{$sezona->slug}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * GET /api/v1/export/sezone/{sezona}/dogadjaji
     * Exportuje sve dogadjaje sezone u CSV format.
     */
    public function dogadjaji(Sezona $sezona): StreamedResponse
    {
        $dogadjaji = $sezona->dogadjaji()->orderBy('datum_dogadjaja')->get();

        return response()->streamDownload(function () use ($sezona, $dogadjaji) {
            $fajl = fopen('php://output', 'w');

            fputs($fajl, "\xEF\xBB\xBF");

            fputcsv($fajl, ['Sezona:', $sezona->naziv]);
            fputcsv($fajl, []);

            fputcsv($fajl, ['ID', 'Naziv', 'Datum', 'Status', 'Max timova', 'Broj rundi']);

            foreach ($dogadjaji as $dogadjaj) {
                fputcsv($fajl, [
                    $dogadjaj->id,
                    $dogadjaj->naziv,
                    $dogadjaj->datum_dogadjaja->format('d.m.Y H:i'),
                    $dogadjaj->status,
                    $dogadjaj->max_timova,
                    $dogadjaj->broj_rundi,
                ]);
            }

            fclose($fajl);
        }, "dogadjaji-{$sezona->slug}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * GET /api/v1/export/sezone/{sezona}/dogadjaji/{dogadjaj}/rezultati
     * Exportuje rezultate jednog dogadjaja u CSV format.
     */
    public function rezultatiDogadjaja(Sezona $sezona, Dogadjaj $dogadjaj): StreamedResponse
    {
        $rezultati = $dogadjaj->rezultati()
            ->with('tim')
            ->orderBy('rang')
            ->get();

        return response()->streamDownload(function () use ($dogadjaj, $rezultati) {
            $fajl = fopen('php://output', 'w');

            fputs($fajl, "\xEF\xBB\xBF");

            fputcsv($fajl, ['Dogadjaj:', $dogadjaj->naziv]);
            fputcsv($fajl, ['Datum:', $dogadjaj->datum_dogadjaja->format('d.m.Y H:i')]);
            fputcsv($fajl, []);

            fputcsv($fajl, ['Rang', 'Tim', 'Bodovi', 'Napomena']);

            foreach ($rezultati as $rezultat) {
                fputcsv($fajl, [
                    $rezultat->rang,
                    $rezultat->tim->naziv,
                    $rezultat->bodovi,
                    $rezultat->napomena ?? '',
                ]);
            }

            fclose($fajl);
        }, "rezultati-{$dogadjaj->slug}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}