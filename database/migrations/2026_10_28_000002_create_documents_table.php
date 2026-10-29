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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // Ukuran berkas dalam bytes
            $table->date('document_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();

            // Indeks untuk pencarian dan pemfilteran cepat berbasis pengguna
            $table->index('user_id');
            $table->index('category_id');
            $table->index(['user_id', 'is_favorite']);
            $table->index(['user_id', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
