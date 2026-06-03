<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TIP: Dodavanje stranog ključa (foreign key)
 * Zamenjuje string kolonu 'lokacija' sa 'lokacija_id' (FK → lokacije.id).
 * Migracija radi u koracima kako bi se sačuvali postojeći podaci:
 *   1. Dodaj novu FK kolonu (nullable za početak)
 *   2. Obriši staru string kolonu
 *   3. Postavi FK ograničenje
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('dogadjaji', function (Blueprint $table) {
            // Korak 1 – dodaj FK kolonu (nullable dok se podaci ne migriraju)
            $table->foreignId('lokacija_id')
                  ->nullable()
                  ->after('sezona_id')
                  ->constrained('lokacije')
                  ->nullOnDelete();

            // Korak 2 – obriši staru tekstualnu kolonu
            $table->dropColumn('lokacija');
        });
    }

    public function down(): void
    {
        Schema::table('dogadjaji', function (Blueprint $table) {
            $table->dropForeign(['lokacija_id']);
            $table->dropColumn('lokacija_id');
            $table->string('lokacija')->nullable()->after('sezona_id');
        });
    }
};