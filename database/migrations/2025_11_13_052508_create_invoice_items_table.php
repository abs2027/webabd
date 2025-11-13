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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            // --- Rincian Baris ---
            $table->string('product_code')->nullable(); // Kolom "Product/Service Code"
            $table->text('description');                // Kolom "Product Description"
            $table->date('item_date')->nullable();      // Kolom "Tanggal" di tabel
            
            $table->decimal('quantity', 10, 2);         // QTY
            $table->string('unit')->default('PCS');     // Unit (PCS, LBR, dll)
            
            $table->decimal('unit_price', 15, 2);       // Price (IDR)
            $table->decimal('total_price', 15, 2);      // Total (IDR) -> (qty * unit_price)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
