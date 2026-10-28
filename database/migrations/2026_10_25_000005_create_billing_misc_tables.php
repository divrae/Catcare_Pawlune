<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 30)->unique();
            $table->foreignId('visit_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('cashier_id')->constrained('users');
            $table->decimal('total_medical', 12, 2)->default(0);
            $table->decimal('total_pharmacy', 12, 2)->default(0);
            $table->decimal('total_boarding', 12, 2)->default(0);
            $table->decimal('total_other', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->string('payment_method', 10); // cash|qris|transfer
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->string('bank_name', 40)->nullable();
            $table->string('reference_no', 60)->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('department', 10); // medical|pharmacy|boarding|other
            $table->string('description');
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('comment');
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        // Notifikasi di dashboard owner (mis. "Anabulmu dipanggil!")
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('info'); // called|info|paid|boarding
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Jejak aktivitas (bahan rekap admin)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notices');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
