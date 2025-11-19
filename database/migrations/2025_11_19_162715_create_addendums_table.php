<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addendums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Judul / Perihal Adendum
            $table->date('date');   // Tanggal
            $table->string('file_path')->nullable(); // Tempat simpan hasil scan
            $table->text('description')->nullable(); // Catatan tambahan (opsional)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addendums');
    }
};
