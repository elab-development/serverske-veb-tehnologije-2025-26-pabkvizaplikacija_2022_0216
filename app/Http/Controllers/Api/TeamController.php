<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tim;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        return Tim::with('ucesca.dogadjaj')->get()->map(function($t){
            return [
                'id' => $t->id,
                'naziv' => $t->naziv,
                'kontakt_email' => $t->kontakt_email,
                'ukupno' => $t->ucesca->sum('bodovi'),
            ];
        })->sortByDesc('ukupno')->values();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'naziv' => 'required|string|unique:teams,naziv',
            'kontakt_email' => 'nullable|email',
        ]);

        $tim = Tim::create($data);

        return response()->json($tim,201);
    }

    public function show(Tim $team)
    {
        return $team->load('ucesca.dogadjaj');
    }
}
