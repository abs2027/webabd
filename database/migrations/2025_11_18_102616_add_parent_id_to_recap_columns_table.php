<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            // Kita tambahkan kolom 'parent_id'
            $table->foreignId('parent_id')
                ->nullable() // Boleh kosong (jika dia kolom induk)
                ->after('project_id') // Posisinya setelah project_id (opsional, agar rapi)
                ->constrained('recap_columns') // Referensi ke tabel itu sendiri
                ->onDelete('set null'); // Jika induk dihapus, anaknya jadi 'null' (top-level)
        });
    }

    public function down(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            // Ini untuk membatalkan (jika ada rollback)
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
