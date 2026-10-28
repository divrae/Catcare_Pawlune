<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tarif layanan (dipakai dokter, boarding, kasir)
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('category', 20)->index(); // pemeriksaan|tindakan|grooming|boarding|lainnya
            $table->string('unit', 20)->default('kali'); // kali|hari
            $table->decimal('price', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Obat: KODE OBAT & KODE BATCH DIGABUNG JADI SATU KODE (mis. OBT-0001)
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 120);
            $table->string('category', 40)->index();
            $table->string('unit', 20);
            $table->decimal('price', 12, 2);
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(10);
            $table->date('expired_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Riwayat keluar-masuk stok (sumber grafik "obat berkurang")
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // in|out|adjust
            $table->integer('qty'); // selalu positif; arah ditentukan type
            $table->integer('stock_after');
            $table->string('reference')->nullable(); // mis. nomor invoice
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Kamar / suite boarding
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number', 20)->unique();
            $table->string('name')->nullable();
            $table->string('room_type', 15)->default('standard'); // standard|deluxe|vip
            $table->foreignId('tariff_id')->nullable()->constrained('tariffs')->nullOnDelete();
            $table->string('qr_hash', 40)->unique();
            $table->boolean('is_maintenance')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('tariffs');
    }
};
