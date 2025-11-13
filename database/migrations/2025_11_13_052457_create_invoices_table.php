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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete(); // Multitenancy

            // --- Info Dasar ---
            $table->string('invoice_number')->unique(); // No. Invoice (e.g., 0064/AAJ...)
            $table->string('po_number')->nullable();    // No. PO (e.g., Direct PO)
            $table->date('invoice_date');               // Tanggal Invoice
            
            // --- Info Pelanggan ---
            $table->string('customer_name');            // Kepada Yth...
            $table->text('customer_address')->nullable();

            // --- Angka & Keuangan ---
            // Kita simpan total-totalnya agar query cepat
            $table->decimal('subtotal', 15, 2)->default(0);    // Jumlah sebelum pajak
            $table->decimal('tax_rate', 5, 2)->default(12);    // Tarif PPN (default 12%)
            $table->decimal('tax_amount', 15, 2)->default(0);  // Nilai PPN
            $table->decimal('total_amount', 15, 2)->default(0); // Total Akhir (Grand Total)

            // --- Lain-lain ---
            $table->text('bank_details')->nullable();   // Info Bank Mandiri
            $table->text('notes')->nullable();          // Catatan tambahan
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
