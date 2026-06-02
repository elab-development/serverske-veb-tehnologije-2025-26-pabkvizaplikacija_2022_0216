<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tim_sezona', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tim_id')->constrained('timovi')->cascadeOnDelete();
            $table->foreignId('sezona_id')->constrained('sezone')->cascadeOnDelete();
            $table->integer('ukupni_bodovi')->default(0);
            $table->integer('odigrani_dogadjaji')->default(0);
            $table->integer('rang')->nullable();
            $table->timestamps();
            $table->unique(['tim_id', 'sezona_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('tim_sezona'); }
};