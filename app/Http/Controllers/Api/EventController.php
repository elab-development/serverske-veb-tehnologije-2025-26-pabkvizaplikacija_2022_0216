<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dogadjaj;
use App\Models\Ucesce;
use App\Models\Tim;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function current()
    {
        return Dogadjaj::trenutni()->with(['sezona','timovi'])->get();
    }

    public function show(Dogadjaj $event)
    {
        return $event->load('sezona','timovi');
    }

    public function azurirajBodove(Request $request, Dogadjaj $event)
    {
        $data = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'bodovi' => 'required|integer',
        ]);

        $ucesce = Ucesce::updateOrCreate(
            ['team_id' => $data['team_id'], 'event_id' => $event->id],
            ['bodovi' => $data['bodovi']]
        );

        $tim = Tim::with('ucesca.dogadjaj')->find($data['team_id']);

        return response()->json([
            'ucesce' => $ucesce,
            'tim_ukupno' => $tim->ucesca->sum('bodovi'),
            'tabla' => Tim::with('ucesca')->get()->map(function($t){
                return [
                    'id' => $t->id,
                    'naziv' => $t->naziv,
                    'ukupno' => $t->ucesca->sum('bodovi'),
                ];
            })->sortByDesc('ukupno')->values(),
        ]);
    }
}
