<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order grooming. Standalone: punya kunjungan sendiri (antrean G-xxx).
        // Tamu boarding: menempel pada kunjungan boarding (boarding_id terisi), biaya ikut tagihan boarding.
        Schema::create('grooming_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boarding_id')->nullable()->constrained('boardings')->nullOnDelete();
            $table->foreignId('groomer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('waiting')->index(); // waiting|called|in_progress|done|cancelled
            $table->text('request_note')->nullable(); // permintaan owner
            $table->string('coat_condition', 150)->nullable(); // kondisi sebelum (kusut, kutu, jamur…)
            $table->text('result_note')->nullable(); // catatan hasil / saran
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::table('visit_services', function (Blueprint $table) {
            $table->foreignId('grooming_order_id')->nullable()->constrained('grooming_orders')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('total_grooming', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn('total_grooming'));
        Schema::table('visit_services', function (Blueprint $t) {
            $t->dropConstrainedForeignId('grooming_order_id');
        });
        Schema::dropIfExists('grooming_orders');
    }
};
