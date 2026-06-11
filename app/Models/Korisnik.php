<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Korisnik extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'korisnici';

    protected $fillable = [
        'ime',
        'prezime',
        'email',
        'lozinka',
        'uloga',        // 'admin' | 'sudija' | 'gledalac'
    ];

    protected $hidden = [
        'lozinka',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'lozinka'           => 'hashed',
    ];

    // Sanctum koristi 'password' kolonu interno — mapiramo na 'lozinka'
    public function getAuthPassword(): string
    {
        return $this->lozinka;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getImeIPrezimeAttribute(): string
    {
        return "{$this->ime} {$this->prezime}";
    }

    public function jeAdmin(): bool
    {
        return $this->uloga === 'admin';
    }
}