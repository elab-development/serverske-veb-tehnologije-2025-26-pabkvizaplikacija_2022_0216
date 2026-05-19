<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sezona;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index()
    {
        return Sezona::with('dogadjaji')->get();
    }

    public function show(Sezona $season)
    {
        return $season->load('dogadjaji.timovi');
    }
}
