<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tim extends Model
{
    use HasFactory;

    protected $table = 'teams';
    protected $fillable = ['naziv','kontakt_email'];

    public function dogadjaji()
    {
        return $this->belongsToMany(Dogadjaj::class, 'participations')
            ->using(Ucesce::class)
            ->withPivot('bodovi')
            ->withTimestamps();
    }

    public function ucesca()
    {
        return $this->hasMany(Ucesce::class);
    }

    public function getUkupnoBodovaAttribute()
    {
        return $this->ucesca()->sum('bodovi');
    }
}
