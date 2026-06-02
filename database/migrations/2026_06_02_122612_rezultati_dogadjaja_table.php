<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rezultati_dogadjaja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dogadjaj_id')->constrained('dogadjaji')->cascadeOnDelete();
            $table->foreignId('tim_id')->constrained('timovi')->cascadeOnDelete();
            $table->integer('bodovi')->default(0);
            $table->integer('rang')->nullable();
            $table->text('napomena')->nullable();
            $table->timestamps();
            $table->unique(['dogadjaj_id', 'tim_id']);
            $table->index(['tim_id', 'dogadjaj_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('rezultati_dogadjaja'); }
};