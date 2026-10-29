<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reminder_id')->nullable()->constrained('reminders')->nullOnDelete();
            $table->string('product_name');
            $table->string('brand');
            $table->string('serial_number')->nullable();
            $table->date('purchase_date');
            $table->date('warranty_end_date');
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('store_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indeks untuk pemfilteran dan pencarian cepat
            $table->index('user_id');
            $table->index('document_id');
            $table->index('reminder_id');
            $table->index(['user_id', 'warranty_end_date']);
            $table->index(['user_id', 'brand']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
