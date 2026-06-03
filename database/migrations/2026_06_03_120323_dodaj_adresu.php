<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('timovi', function (Blueprint $table) {
            $table->string('adresa')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('timovi', function (Blueprint $table) {
            $table->dropColumn('adresa');
        });
    }
};