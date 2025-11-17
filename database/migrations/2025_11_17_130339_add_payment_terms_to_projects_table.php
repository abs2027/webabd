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
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('payment_term_value')->nullable()->after('status');
            $table->string('payment_term_unit')->nullable()->after('payment_term_value'); // Misal: 'days', 'months', 'years'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ...
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['payment_term_value', 'payment_term_unit']);
        });
        // ...
    }
};
