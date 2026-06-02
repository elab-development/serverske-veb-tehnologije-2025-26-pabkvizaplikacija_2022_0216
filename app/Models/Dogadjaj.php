<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dogadjaj extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dogadjaji';

    const STATUS_NADOLAZECI = 'nadolazeci';
    const STATUS_U_TOKU     = 'u_toku';
    const STATUS_ZAVRSEN    = 'zavrsen';

    protected $fillable = [
        'sezona_id',
        'naziv',
        'slug',
        'datum_dogadjaja',
        'lokacija',
        'opis',
        'status',
        'max_timova',
        'broj_rundi',
    ];

    protected $casts = [
        'datum_dogadjaja' => 'datetime',
        'max_timova'      => 'integer',
        'broj_rundi'      => 'integer',
    ];


    public function sezona(): BelongsTo
    {
        return $this->belongsTo(Sezona::class);
    }

    public function rezultati(): HasMany
    {
        return $this->hasMany(RezultatDogadjaja::class);
    }

    public function timovi(): BelongsToMany
    {
        return $this->belongsToMany(Tim::class, 'rezultati_dogadjaja')
                    ->withPivot(['bodovi', 'rang', 'napomena'])
                    ->withTimestamps();
    }


    public function scopeNadolazeci($query)
    {
        return $query->where('status', self::STATUS_NADOLAZECI)->orderBy('datum_dogadjaja');
    }

    public function scopeUToku($query)
    {
        return $query->where('status', self::STATUS_U_TOKU);
    }

    public function scopeZavrsen($query)
    {
        return $query->where('status', self::STATUS_ZAVRSEN);
    }

    public function scopeAktivan($query)
    {
        return $query->whereIn('status', [self::STATUS_NADOLAZECI, self::STATUS_U_TOKU]);
    }


    public function getJeNadolazaciAttribute(): bool { return $this->status === self::STATUS_NADOLAZECI; }
    public function getJeUTokuAttribute(): bool      { return $this->status === self::STATUS_U_TOKU; }
    public function getJeZavrsenAttribute(): bool    { return $this->status === self::STATUS_ZAVRSEN; }

    /** Rezultati sortirani po rangu (1. mesto prvo). */
    public function getRangiranResultatiAttribute()
    {
        return $this->rezultati()->with('tim')->orderBy('rang')->get();
    }
}