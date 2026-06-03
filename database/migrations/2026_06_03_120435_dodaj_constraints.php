<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TIP: Postavljanje dodatnih ograničenja (constraints)
 * - bodovi >= 0 (CHECK constraint)
 * - rang >= 1   (CHECK constraint)
 * - Unique index na (dogadjaj_id, rang) – dva tima ne mogu imati isti rang na istom događaju
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('rezultati_dogadjaja', function (Blueprint $table) {
            // Unique: isti rang ne može biti dodeljen dva puta na istom događaju
            $table->unique(['dogadjaj_id', 'rang'], 'unique_rang_po_dogadjaju');
        });

        // CHECK constraints (raw SQL – Laravel nema fluent API za CHECK)
        DB::statement('ALTER TABLE rezultati_dogadjaja ADD CONSTRAINT chk_bodovi_pozitivni CHECK (bodovi >= 0)');
        DB::statement('ALTER TABLE rezultati_dogadjaja ADD CONSTRAINT chk_rang_pozitivan CHECK (rang >= 1)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE rezultati_dogadjaja DROP CONSTRAINT chk_bodovi_pozitivni');
        DB::statement('ALTER TABLE rezultati_dogadjaja DROP CONSTRAINT chk_rang_pozitivan');

        Schema::table('rezultati_dogadjaja', function (Blueprint $table) {
            $table->dropUnique('unique_rang_po_dogadjaju');
        });
    }
};