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
        Schema::create('recap_rows', function (Blueprint $table) {
            $table->id();

            // ▼▼▼ UBAH INI ▼▼▼
            // $table->foreignId('project_id')->constrained()->cascadeOnDelete(); // HAPUS INI
            $table->foreignId('recap_id')->constrained()->cascadeOnDelete(); // TAMBAH INI

            // Kolom JSON 'data' Anda (biarkan seperti aslinya)
            $table->json('data')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recap_rows');
    }
};
