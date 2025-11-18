<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->boolean('is_summarized')->default(false)->after('options');
        });
    }

    public function down(): void
    {
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->dropColumn('is_summarized');
        });
    }
};
