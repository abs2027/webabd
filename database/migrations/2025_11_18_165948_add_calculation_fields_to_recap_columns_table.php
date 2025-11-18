<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            // Field untuk menyimpan nama kolom operand A
            $table->string('operand_a')->nullable()->after('order'); 
            // Field untuk menyimpan operator (*, +, -, /)
            $table->string('operator')->nullable()->after('operand_a');
            // Field untuk menyimpan nama kolom operand B
            $table->string('operand_b')->nullable()->after('operator');
        });
    }

    public function down(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->dropColumn(['operand_a', 'operator', 'operand_b']);
        });
    }
};
