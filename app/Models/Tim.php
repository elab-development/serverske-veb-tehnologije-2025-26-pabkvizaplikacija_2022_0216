<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tim extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'timovi';

    protected $fillable = [
        'naziv',
        'slug',
        'kontakt_email',
        'kontakt_telefon',
        'logo_url',
        'aktivan',
    ];

    protected $casts = [
        'aktivan' => 'boolean',
    ];


    public function sezone(): BelongsToMany
    {
        return $this->belongsToMany(Sezona::class, 'tim_sezona')
                    ->withPivot(['ukupni_bodovi', 'rang', 'odigrani_dogadjaji'])
                    ->withTimestamps();
    }

    public function rezultatiDogadjaja(): HasMany
    {
        return $this->hasMany(RezultatDogadjaja::class);
    }


    public function scopeAktivan($query)
    {
        return $query->where('aktivan', true);
    }


    /** Ukupni bodovi tima u datoj sezoni. */
    public function ukupniBodoviUSezoni(int $sezonaId): int
    {
        return $this->rezultatiDogadjaja()
                    ->whereHas('dogadjaj', fn($q) => $q->where('sezona_id', $sezonaId))
                    ->sum('bodovi');
    }
}