<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat Tabel Recap Types
        Schema::create('recap_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Contoh: "Rekap Pipa", "Rekap Solar"
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tambahkan kolom recap_type_id ke tabel anak (nullable dulu)
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->foreignId('recap_type_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::table('recaps', function (Blueprint $table) {
            $table->foreignId('recap_type_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // =========================================================
        // 3. MIGRASI DATA LAMA (PENTING!)
        // Kita buatkan "Default Recap Type" untuk setiap Project yang sudah ada
        // agar data lama masuk ke sana.
        // =========================================================
        
        $projects = DB::table('projects')->get();

        foreach ($projects as $project) {
            // Buat 1 tipe rekap default bernama "Rekapitulasi Umum"
            $typeId = DB::table('recap_types')->insertGetId([
                'project_id' => $project->id,
                'name' => 'Rekapitulasi Umum', // Nama default
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update semua kolom milik project ini ke tipe baru
            DB::table('recap_columns')
                ->where('project_id', $project->id)
                ->update(['recap_type_id' => $typeId]);

            // Update semua periode rekap milik project ini ke tipe baru
            DB::table('recaps')
                ->where('project_id', $project->id)
                ->update(['recap_type_id' => $typeId]);
        }

        // 4. Hapus kolom project_id yang lama (Clean up)
        // Hati-hati: Pastikan langkah 3 sukses sebelum jalankan migrate
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->dropForeign(['project_id']); // Drop foreign key constraint dulu
            $table->dropColumn('project_id');
        });

        Schema::table('recaps', function (Blueprint $table) {
            $table->dropForeign(['project_id']); // Drop foreign key constraint dulu
            $table->dropColumn('project_id');
        });
        
        // Jadikan recap_type_id wajib (not null) setelah data terisi
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->unsignedBigInteger('recap_type_id')->nullable(false)->change();
        });
        Schema::table('recaps', function (Blueprint $table) {
            $table->unsignedBigInteger('recap_type_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        // Rollback logic (agak rumit karena data sudah bergeser, 
        // tapi minimal kita drop tabel barunya)
        Schema::table('recaps', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->dropColumn('recap_type_id');
        });
        
        Schema::table('recap_columns', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained();
            $table->dropColumn('recap_type_id');
        });

        Schema::dropIfExists('recap_types');
    }
};
