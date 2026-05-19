<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Dogadjaj extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $fillable = ['season_id','naslov','opis','pocetak','status'];

    protected $dates = ['pocetak'];

    public function sezona()
    {
        return $this->belongsTo(Sezona::class, 'season_id');
    }

    public function timovi()
    {
        return $this->belongsToMany(Tim::class, 'participations')
            ->using(Ucesce::class)
            ->withPivot('bodovi')
            ->withTimestamps();
    }

    public function ucesca()
    {
        return $this->hasMany(Ucesce::class);
    }

    public function scopeTrenutni($query)
    {
        $now = Carbon::now();
        return $query->where(function($q) use ($now) {
            $q->where('status','u_toku')
              ->orWhere(function($q2) use ($now) {
                  $q2->where('pocetak','>=',$now);
              });
        });
    }
}
