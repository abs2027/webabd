<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            // Field untuk menyimpan opsi dropdown (dipisahkan koma)
            $table->text('options')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }
};
