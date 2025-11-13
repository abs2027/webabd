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
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete(); // Pengait ke PT

            // Detail 'Kepala'
            $table->string('order_number')->unique(); // No. Surat Jalan
            $table->date('date_of_issue');           // Tanggal Surat Dibuat
            $table->string('customer_name');         // Nama Pelanggan
            $table->text('customer_address');        // Alamat Pelanggan
            $table->string('driver_name');           // Nama Sopir
            $table->string('vehicle_plate_number');  // No. Polisi Kendaraan
            $table->text('notes')->nullable();       // Catatan
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_orders');
    }
};
