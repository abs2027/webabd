<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Set; // Penting untuk kalkulasi
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ... (Section Informasi Dasar & Pelanggan - tidak berubah) ...
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('invoice_number')->label('No. Invoice')->required()->maxLength(255)->unique(ignoreRecord: true),
                        TextInput::make('po_number')->label('No. PO')->maxLength(255),
                        DatePicker::make('invoice_date')->label('Tanggal Invoice')->required()->default(now()),
                    ])->columns(3),

                Section::make('Informasi Pelanggan')
                    ->schema([
                        TextInput::make('customer_name')->label('Nama Pelanggan')->required(),
                        Textarea::make('customer_address')->label('Alamat Pelanggan')->rows(3)->columnSpanFull(),
                    ]),
                
                Section::make('Rincian Tagihan')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                // ... (Field lain di repeater - tidak berubah) ...
                                TextInput::make('product_code')->label('Kode Produk/Servis')->columnSpan(1),
                                Textarea::make('description')->label('Deskripsi Produk')->required()->columnSpan(5),

                                // --- PERUBAHAN PERHITUNGAN PPN DI SINI ---
                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()->required()->default(1)
                                    ->live(onBlur: true) 
                                    ->afterStateUpdated(function (Set $set, $state, $get) {
                                        $totalPrice = floatval($state) * floatval($get('unit_price') ?? 0);
                                        $set('total_price', $totalPrice);
                                        
                                        // Hitung ulang total keseluruhan
                                        $items = $get('../../items');
                                        $subtotal = 0;
                                        foreach ($items as $item) {
                                            $subtotal += (floatval($item['quantity'] ?? 0) * floatval($item['unit_price'] ?? 0));
                                        }
                                        $set('../../subtotal', $subtotal);
                                        $taxAmount = $subtotal * 0.11; // <-- DIUBAH KE 11%
                                        $set('../../tax_amount', $taxAmount);
                                        $set('../../total_amount', $subtotal + $taxAmount);
                                    })
                                    ->columnSpan(2),

                                TextInput::make('unit')->label('Unit')->default('PCS')->columnSpan(1),

                                // --- PERUBAHAN PERHITUNGAN PPN DI SINI ---
                                TextInput::make('unit_price')
                                    ->label('Harga Satuan (IDR)')
                                    ->numeric()->required()->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, $state, $get) {
                                        $totalPrice = floatval($state) * floatval($get('quantity') ?? 0);
                                        $set('total_price', $totalPrice);
                                        
                                        // Hitung ulang total keseluruhan
                                        $items = $get('../../items');
                                        $subtotal = 0;
                                        foreach ($items as $item) {
                                            $subtotal += (floatval($item['quantity'] ?? 0) * floatval($item['unit_price'] ?? 0));
                                        }
                                        $set('../../subtotal', $subtotal);
                                        $taxAmount = $subtotal * 0.11; // <-- DIUBAH KE 11%
                                        $set('../../tax_amount', $taxAmount);
                                        $set('../../total_amount', $subtotal + $taxAmount);
                                    })
                                    ->columnSpan(2),

                                TextInput::make('total_price')->label('Total (IDR)')->numeric()->required()->default(0)->disabled()->columnSpan(1),
                            ])
                            ->columns(6)
                            ->addActionLabel('Tambah Item')
                            ->columnSpanFull()
                            ->live()
                            // --- PERUBAHAN PERHITUNGAN PPN DI SINI ---
                            ->afterStateUpdated(function (Set $set, $state) {
                                $subtotal = 0;
                                foreach ($state as $item) {
                                    $quantity = $item['quantity'] ?? 0;
                                    $unit_price = $item['unit_price'] ?? 0;
                                    $subtotal += floatval($quantity) * floatval($unit_price);
                                }
                                $set('subtotal', $subtotal); 
                                $taxAmount = $subtotal * 0.11; // <-- DIUBAH KE 11%
                                $set('tax_amount', $taxAmount);
                                $set('total_amount', $subtotal + $taxAmount);
                            }),
                    ]),

                // ==========================================================
                // ▼▼▼ BAGIAN TOTAL (PERUBAHAN LABEL & DEFAULT) ▼▼▼
                // ==========================================================
                Section::make('Total')
                    ->schema([
                        TextInput::make('subtotal')->label('Subtotal')->numeric()->readOnly()->prefix('Rp'),
                        
                        Hidden::make('tax_rate')->default(11), // <-- DIUBAH KE 11%

                        TextInput::make('tax_amount')
                            ->label('PPN (12%)')
                            ->numeric()
                            ->readOnly()
                            ->prefix('Rp'),

                        TextInput::make('total_amount')->label('TOTAL (IDR)')->numeric()->readOnly()->prefix('Rp')->extraAttributes(['class' => 'font-bold text-lg']),
                    ])->columns(3),
                
                // ... (Section Info Tambahan - tidak berubah) ...
                Section::make('Informasi Tambahan')
                    ->schema([
                        Textarea::make('bank_details')->label('Detail Bank Pembayaran')->rows(4)->default(fn () => "Pembayaran akan dilakukan melalui:\nBank Mandiri\na/c : 163-000-7799771\na/n : PT. ABD ABADI JAYA"),
                        Textarea::make('notes')->label('Catatan Tambahan')->rows(3),
                    ])->columns(2),
            ]);
    }

    // ... (Method table(), getRelations(), getPages() - tidak berubah) ...
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('No. Invoice')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Pelanggan')->searchable(),
                TextColumn::make('invoice_date')->label('Tanggal')->date()->sortable(),
                TextColumn::make('total_amount')->label('Total (IDR)')->numeric(2, ',', '.')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('printInvoice')
                    ->label('Cetak Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(function (Invoice $record) {
                        return route('print.invoice', [
                            'tenant' => filament()->getTenant(),
                            'record' => $record
                        ]);
                    })
                    ->openUrlInNewTab(),
                    
                Action::make('printReceipt')
                    ->label('Cetak Kwitansi')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('info') // Warna biru/info
                    ->url(function (Invoice $record) {
                        return route('print.receipt', [ // Mengarah ke rute kwitansi
                            'tenant' => filament()->getTenant(),
                            'record' => $record
                        ]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
