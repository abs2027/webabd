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
        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();

            // --- Kunci Penghubung ke 'Kepala' ---
            $table->foreignId('delivery_order_id')->constrained()->cascadeOnDelete();

            // --- Detail Item Barang ---
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();      // "Kode SKU"
            $table->decimal('quantity', 8, 2);  // "Kuantitas"
            $table->string('unit');                 // "Unit" (e.g., Piece, Kg, Box)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
    }
};
