<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kunjungan = pusat integrasi (antrean -> dokter -> boarding -> kasir)
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_code', 30)->unique();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->string('service_type', 10); // clinic|boarding
            $table->string('source', 10)->default('online'); // online|walk_in
            $table->date('queue_date');
            $table->unsignedInteger('queue_number');
            $table->text('complaint')->nullable();
            $table->string('status', 20)->default('waiting')->index();
            // waiting|called|in_treatment|in_boarding|awaiting_payment|completed|cancelled
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('payment_status', 10)->default('unpaid'); // unpaid|paid
            $table->timestamps();
            $table->unique(['queue_date', 'service_type', 'queue_number']);
        });

        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users');
            $table->text('anamnesis')->nullable();
            $table->text('diagnosis');
            $table->text('treatment_note')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->date('follow_up_date')->nullable();
            $table->boolean('referred_to_boarding')->default(false);
            $table->timestamps();
        });

        // Resep obat dokter -> terhubung ke master obat (harga & stok)
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->constrained();
            $table->string('medicine_name', 120); // snapshot
            $table->string('category', 40)->nullable();
            $table->integer('qty');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->string('dosage', 80)->nullable();
            $table->string('instruction', 150)->nullable();
            $table->timestamps();
        });

        // Layanan/tindakan pada kunjungan (dari tarif)
        Schema::create('visit_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tariff_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('category', 20);
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->string('added_by_role', 10)->default('doctor'); // doctor|cashier
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_services');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('visits');
    }
};
