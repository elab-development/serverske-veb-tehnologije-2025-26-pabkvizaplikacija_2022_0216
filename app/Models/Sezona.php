<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sezona extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sezone';

    protected $fillable = [
        'naziv',
        'slug',
        'datum_pocetka',
        'datum_zavrsetka',
        'aktivna',
        'opis',
    ];

    protected $casts = [
        'datum_pocetka'    => 'date',
        'datum_zavrsetka'  => 'date',
        'aktivna'          => 'boolean',
    ];


    public function dogadjaji(): HasMany
    {
        return $this->hasMany(Dogadjaj::class);
    }

    public function timovi(): BelongsToMany
    {
        return $this->belongsToMany(Tim::class, 'tim_sezona')
                    ->withPivot(['ukupni_bodovi', 'rang', 'odigrani_dogadjaji'])
                    ->withTimestamps();
    }


    public function scopeAktivna($query)
    {
        return $query->where('aktivna', true);
    }


    /** Tabela rezultata – timovi sortirani po ukupnim bodovima. */
    public function tabelaRezultata()
    {
        return $this->timovi()
                    ->orderByPivot('ukupni_bodovi', 'desc')
                    ->get();
    }
}