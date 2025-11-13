<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderResource\Pages;
use App\Models\DeliveryOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// TAMBAHKAN SEMUA IMPORT INI
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater; // <-- Import untuk Item Barang
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Blade;
use Barryvdh\DomPDF\Facade\Pdf;

class DeliveryOrderResource extends Resource
{
    protected static ?string $model = DeliveryOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Mengelompokkan field agar rapi
                Section::make('Detail Utama')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('No. Surat Jalan')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true), 
                        
                        DatePicker::make('date_of_issue')
                            ->label('Tanggal Diterbitkan')
                            ->required()
                            ->default(now()),
                    ])->columns(2),

                Section::make('Detail Pelanggan')
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Nama Pelanggan')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('customer_address')
                            ->label('Alamat Pelanggan')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Detail Pengiriman')
                    ->schema([
                        TextInput::make('driver_name')
                            ->label('Nama Sopir')
                            ->required()
                            ->maxLength(255),
                        
                        TextInput::make('vehicle_plate_number')
                            ->label('No. Polisi Kendaraan')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                // ==========================================================
                // ▼▼▼ BAGIAN BARU UNTUK ITEM BARANG (SESUAI CONTOH ANDA) ▼▼▼
                // ==========================================================
                Section::make('Item Barang')
                    ->schema([
                        Repeater::make('items') // Ini terhubung ke relasi 'items()' di Model
                            ->relationship()
                            ->schema([
                                TextInput::make('product_name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->columnSpan(2), // Ambil 2 slot

                                TextInput::make('sku')
                                    ->label('Kode SKU'),

                                TextInput::make('quantity')
                                    ->label('Kuantitas')
                                    ->numeric()
                                    ->required()
                                    ->default(1),

                                TextInput::make('unit')
                                    ->label('Unit')
                                    ->required()
                                    ->default('Piece'),

                                Textarea::make('description')
                                    ->label('Deskripsi Produk')
                                    ->columnSpanFull(), // Lebar penuh
                            ])
                            ->columns(5) // 5 kolom per baris item
                            ->addActionLabel('Tambah Item')
                            ->columnSpanFull(),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan Tambahan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // Ini adalah tampilan daftar surat jalan (kita buat simpel)
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Surat Jalan')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->searchable(),
                
                TextColumn::make('date_of_issue')
                    ->label('Tanggal Terbit')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('driver_name')
                    ->label('Nama Sopir')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // ▼▼▼ TOMBOL BARU UNTUK CETAK PDF ▼▼▼
                Action::make('printPdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(function (DeliveryOrder $record) {
                        // Membuat URL yang aman untuk rute cetak kita
                        return route('print.delivery-order', [
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
            'index' => Pages\ListDeliveryOrders::route('/'),
            'create' => Pages\CreateDeliveryOrder::route('/create'),
            // Kita ubah halaman Edit agar bisa melihat item
            'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'), 
        ];
    }    
}