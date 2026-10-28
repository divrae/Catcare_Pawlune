<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jadwal shift staff (diatur admin). 1 staff = maks 1 shift per hari.
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('shift_date');
            $table->string('shift_type', 10); // pagi|siang|malam
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'shift_date']);
        });

        // Anabul + Soulmate Pass
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('pass_code', 20)->unique(); // kode Soulmate Pass (isi QR)
            $table->string('name', 60);
            $table->string('species', 20)->default('kucing'); // kucing|anjing|kelinci|burung|lainnya
            $table->string('breed', 60)->default('Domestik');
            $table->string('gender', 10)->default('jantan'); // jantan|betina
            $table->date('birth_date')->nullable();
            $table->string('temperament', 15)->default('tenang'); // tenang|cemas|agresif
            $table->string('photo')->nullable();
            $table->text('allergy_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
        Schema::dropIfExists('shifts');
    }
};
