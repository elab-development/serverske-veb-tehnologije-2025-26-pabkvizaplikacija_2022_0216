<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->integer('bodovi')->default(0);
            $table->timestamps();
            $table->unique(['team_id','event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations');
    }
};
