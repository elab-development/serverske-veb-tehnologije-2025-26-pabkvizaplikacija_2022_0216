<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ucesce extends Model
{
    protected $table = 'participations';

    protected $fillable = ['team_id','event_id','bodovi'];

    public function tim()
    {
        return $this->belongsTo(Tim::class, 'team_id');
    }

    public function dogadjaj()
    {
        return $this->belongsTo(Dogadjaj::class, 'event_id');
    }
}
