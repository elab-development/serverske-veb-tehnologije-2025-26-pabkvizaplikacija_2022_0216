<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sezona extends Model
{
    use HasFactory;

    protected $table = 'seasons';
    protected $fillable = ['naziv','pocetak','kraj'];

    public function dogadjaji()
    {
        return $this->hasMany(Dogadjaj::class);
    }
}
