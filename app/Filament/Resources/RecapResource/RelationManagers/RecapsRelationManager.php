<?php

namespace App\Filament\Resources\RecapResource\RelationManagers;

use App\Filament\Resources\RecapResource;
use App\Models\Recap;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Arr; // Penting untuk mengolah JSON

class RecapsRelationManager extends RelationManager
{
    protected static string $relationship = 'recaps';

    protected static ?string $title = '2. Periode Rekapitulasi';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Periode')
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Tanggal Mulai'),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Tanggal Selesai'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Periode')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Tgl. Mulai')
                    ->date(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Tgl. Selesai')
                    ->date(),

                // ▼▼▼ 1. KOLOM JUMLAH DATA ▼▼▼
                Tables\Columns\TextColumn::make('recap_rows_count')
                    ->counts('recapRows')
                    ->label('Jml. Data')
                    ->badge()
                    ->color('info'),

                // ▼▼▼ 2. KOLOM RINGKASAN TOTAL (NOMINAL) ▼▼▼
                Tables\Columns\TextColumn::make('total_summary')
                    ->label('Ringkasan Total')
                    ->state(function (Recap $record) {
                        // 1. Load struktur kolom (agar tidak query berulang-ulang di loop)
                        $record->load('recapType.recapColumns');
                        
                        // 2. Cari kolom target: Tipe Money DAN Summarized
                        $targetColumn = $record->recapType->recapColumns
                            ->where('type', 'money')
                            ->where('is_summarized', true)
                            ->first(); // Ambil yang pertama ketemu

                        // Jika tidak ada kolom yang diset sebagai summary, tampilkan strip
                        if (!$targetColumn) return '-';

                        // 3. Hitung total dari JSON
                        $total = 0;
                        foreach ($record->recapRows as $row) {
                            // Flatten data karena mungkin ada di dalam Group/Folder
                            $flatData = Arr::dot($row->data ?? []);
                            
                            foreach ($flatData as $key => $val) {
                                // Cek apakah key JSON berakhiran dengan nama kolom target
                                if (str_ends_with($key, $targetColumn->name)) {
                                    // Bersihkan format string (Rp 10.000 -> 10000)
                                    $cleanVal = str_replace(['Rp', '.', ' '], '', $val);
                                    $cleanVal = str_replace(',', '.', $cleanVal);
                                    $total += (float) $cleanVal;
                                    break; // Lanjut ke baris berikutnya
                                }
                            }
                        }

                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->description(function (Recap $record) {
                         // Tampilkan nama kolomnya di bawah angka agar user tau ini total apa
                         $targetColumn = $record->recapType->recapColumns
                            ->where('type', 'money')
                            ->where('is_summarized', true)
                            ->first();
                         return $targetColumn ? $targetColumn->name : null;
                    })
                    ->badge()
                    ->color('success'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tombol Shortcut Input Data
                Tables\Actions\Action::make('Input Data')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record): string => RecapResource::getUrl('view', ['record' => $record])), 
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}