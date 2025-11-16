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
        Schema::table('users', function (Blueprint $table) {
            // ▼▼▼ TAMBAHKAN BARIS INI ▼▼▼
            $table->foreignId('company_id')
                  ->after('email')     // (Opsional: agar posisinya rapi setelah kolom email)
                  ->nullable()         // (PENTING: agar tidak error jika tabel users sudah ada isinya)
                  ->constrained('companies') // (Pastikan nama tabel Anda 'companies')
                  ->cascadeOnDelete(); // (Jika company dihapus, user-nya juga ikut terhapus)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
