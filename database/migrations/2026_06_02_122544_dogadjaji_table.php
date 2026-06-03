<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dogadjaji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sezona_id')->constrained('sezone')->cascadeOnDelete();
            $table->string('naziv');
            $table->string('slug')->unique();
            $table->dateTime('datum_dogadjaja');
            $table->string('lokacija')->nullable();
            $table->text('opis')->nullable();
            $table->enum('status', ['nadolazeci', 'u_toku', 'zavrsen'])->default('nadolazeci');
            $table->integer('max_timova')->default(20);
            $table->integer('broj_rundi')->default(5);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['sezona_id', 'status']);
            $table->index('datum_dogadjaja');
        });
    }
    public function down(): void { Schema::dropIfExists('dogadjaji'); }
};