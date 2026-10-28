<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('status', 12)->default('reserved')->index(); // reserved|active|finished|cancelled
            $table->boolean('is_referral')->default(false); // rujukan dokter
            $table->text('referral_note')->nullable();
            $table->date('check_in_date');
            $table->date('check_out_date'); // rencana, lalu diisi realisasi saat check-out
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->decimal('rate_per_day', 12, 2)->default(0); // snapshot tarif saat check-in
            $table->text('diet_note')->nullable(); // pakan/kebiasaan
            $table->text('belongings')->nullable(); // barang bawaan
            $table->foreignId('admitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Catatan harian: makan, suhu, kesehatan
        Schema::create('boarding_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boarding_id')->constrained()->cascadeOnDelete();
            $table->dateTime('logged_at');
            $table->string('meal', 15)->default('habis'); // habis|sebagian|tidak_makan
            $table->string('food_note')->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->string('health_status', 15)->default('sehat'); // sehat|perhatian|sakit
            $table->string('stool', 15)->default('normal'); // normal|lembek|diare|belum
            $table->boolean('medicine_given')->default(false);
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boarding_logs');
        Schema::dropIfExists('boardings');
    }
};
