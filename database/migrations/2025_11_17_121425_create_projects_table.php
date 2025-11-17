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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete(); // Penting untuk Tenant
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete(); // Dihubungkan ke Klien

            $table->string('name'); // Nama Proyek
            $table->text('description')->nullable(); // Deskripsi Proyek

            $table->string('status')->default('Baru'); // Misal: Baru, Berjalan, Selesai

            $table->date('start_date')->nullable(); // Tgl Mulai
            $table->date('end_date')->nullable();   // Tgl Selesai

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
