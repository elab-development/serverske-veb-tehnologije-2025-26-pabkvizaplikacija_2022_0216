<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RezultatDogadjaja extends Model
{
    use HasFactory;

    protected $table = 'rezultati_dogadjaja';

    protected $fillable = [
        'dogadjaj_id',
        'tim_id',
        'bodovi',
        'rang',
        'napomena',
    ];

    protected $casts = [
        'bodovi' => 'integer',
        'rang'   => 'integer',
    ];


    public function dogadjaj(): BelongsTo
    {
        return $this->belongsTo(Dogadjaj::class);
    }

    public function tim(): BelongsTo
    {
        return $this->belongsTo(Tim::class);
    }


    /**
     * Nakon unosa rezultata, ažurira tabelu rezultata (tim_sezona pivot)
     * za sezonu kojoj ovaj događaj pripada.
     */
    public function sinhronizujTabeluSezone(): void
    {
        $sezona = $this->dogadjaj->sezona;

        $ukupniBodovi = RezultatDogadjaja::query()
            ->where('tim_id', $this->tim_id)
            ->whereHas('dogadjaj', fn($q) => $q->where('sezona_id', $sezona->id))
            ->sum('bodovi');

        $odigrani = RezultatDogadjaja::query()
            ->where('tim_id', $this->tim_id)
            ->whereHas('dogadjaj', fn($q) => $q->where('sezona_id', $sezona->id))
            ->count();

        $sezona->timovi()->syncWithoutDetaching([
            $this->tim_id => [
                'ukupni_bodovi'       => $ukupniBodovi,
                'odigrani_dogadjaji'  => $odigrani,
            ],
        ]);
    }
}