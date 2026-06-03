<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIP: Kreiranje nove tabele
 * Izvlačimo lokacije u zasebnu tabelu kako bi se mogle koristiti
 * na više događaja i pratiti statistike po lokaciji.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('lokacije', function (Blueprint $table) {
            $table->id();
            $table->string('naziv');
            $table->string('adresa');
            $table->string('grad');
            $table->unsignedSmallInteger('kapacitet')->nullable();
            $table->string('kontakt_email')->nullable();
            $table->string('kontakt_telefon')->nullable();
            $table->boolean('aktivna')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lokacije');
    }
};