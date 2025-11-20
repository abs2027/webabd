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
        Schema::table('recap_columns', function (Blueprint $table) {
            // Kita tambah kolom 'role' untuk menyimpan jabatan kolom
            // Defaultnya 'none' (tidak punya jabatan khusus)
            $table->string('role')->default('none')->after('type'); 
        });
    }

    public function down(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
