<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea; 
use Filament\Forms\Components\Section; // PENTING: Pakai Section
use Filament\Forms\Components\Grid;    // PENTING: Pakai Grid
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use App\Models\RecapColumn; 

class RecapColumnsRelationManager extends RelationManager
{
    protected static string $relationship = 'recapColumns';
    protected static ?string $title = '1. Desain Tabel Rekapitulasi';
    
    public function isReadOnly(): bool
    {
        return false;
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                
                // --- BAGIAN 1: IDENTITAS UTAMA (Wajib Diisi) ---
                Section::make('Definisi Kolom')
                    ->description('Tentukan nama dan fungsi kolom untuk kebutuhan Dashboard KPI.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kolom')
                            ->required()
                            ->placeholder('Contoh: Total Harga, Lokasi, Shift')
                            ->columnSpan(1),

                        TextInput::make('order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(1),

                        Select::make('type')
                            ->label('Tipe Kolom')
                            ->options([
                                'text'   => '🔤 Teks Singkat (Nama, Catatan)',
                                'select' => '🔽 Pilihan / Dropdown', // Lebih singkat & jelas
                                'number' => '🔢 Angka (Qty, Nomor)',
                                'money'  => '💰 Uang (Rp)', 
                                'date'   => '📅 Tanggal',
                                'group'  => '📂 Grup (Folder Pembungkus)',
                            ])
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        // ▼▼▼ PANDUAN KPI YANG LEBIH JELAS ▼▼▼
                        Select::make('role')
                            ->label('Peran di Dashboard (PENTING)')
                            ->options([
                                'none' => '📝 Data Biasa (Hanya tampil di tabel)',
                                'dimension' => '📊 Kategori / Sumbu X (Untuk Grafik Potongan/Batang)',
                                'metric_sum' => '📈 Nilai Utama / Sumbu Y (Untuk Dijumlahkan & Statistik)',
                            ])
                            ->default('none')
                            ->required()
                            ->selectablePlaceholder(false)
                            ->helperText(new \Illuminate\Support\HtmlString(
                                '<strong>Panduan:</strong><br>' .
                                '• Pilih <strong>Kategori</strong> untuk: Tempat, Shift, Vendor.<br>' .
                                '• Pilih <strong>Nilai Utama</strong> untuk: Harga, Total, Qty, Omzet.'
                            ))
                            // ▲▲▲ SELESAI PERBAIKAN ▲▲▲
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // --- BAGIAN 2: KONFIGURASI LANJUTAN (Muncul Sesuai Tipe) ---
                Section::make('Konfigurasi Lanjutan')
                    ->schema([
                        
                        // A. KHUSUS DROPDOWN (SELECT)
                        Textarea::make('options')
                            ->label('Daftar Pilihan (Pisahkan dengan Koma)')
                            ->placeholder('Contoh: Pagi, Siang, Sore, Malam')
                            ->rows(3)
                            ->visible(fn (Get $get) => $get('type') === 'select')
                            ->required(fn (Get $get) => $get('type') === 'select')
                            ->helperText('User nanti tinggal memilih salah satu opsi ini.'),

                        // B. STRUKTUR HIRARKI (PARENT)
                        Select::make('parent_id')
                            ->label('Masukkan ke dalam Grup?')
                            ->searchable()
                            ->placeholder('Pilih Grup Induk (Opsional)')
                            ->options(function () {
                                $project = $this->getOwnerRecord();
                                return $project ? $project->recapColumns()->where('type', 'group')->pluck('name', 'id') : [];
                            })
                            ->visible(fn (Get $get) => $get('type') !== 'group'), // Grup tidak bisa masuk grup

                        // C. TOTAL DI FOOTER
                        Toggle::make('is_summarized')
                            ->label('Hitung Total (Sum) di Bawah Tabel?')
                            ->inline(false)
                            ->visible(fn (Get $get) => in_array($get('type'), ['number', 'money', 'select'])),

                    ])
                    ->collapsible()
                    ->compact(),

                // --- BAGIAN 3: KALKULASI OTOMATIS (Rumus) ---
                Section::make('Rumus Otomatis (Opsional)')
                    ->description('Isi jika kolom ini adalah hasil hitungan kolom lain.')
                    ->icon('heroicon-m-calculator')
                    ->collapsed()
                    ->visible(fn (Get $get) => in_array($get('type'), ['number', 'money']))
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('operand_a')
                                    ->label('Kolom A')
                                    ->placeholder('Nama Kolom..'),
                                
                                Select::make('operator')
                                    ->label('Operasi')
                                    ->options([
                                        '*' => '( x ) Dikali',
                                        '/' => '( : ) Dibagi',
                                        '+' => '( + ) Ditambah',
                                        '-' => '( - ) Dikurang',
                                    ]),
                                
                                TextInput::make('operand_b')
                                    ->label('Kolom B')
                                    ->placeholder('Nama Kolom..'),
                            ]),

                        // ▼▼▼ SOLUSI: Pakai Placeholder untuk teks di bawah ▼▼▼
                        Forms\Components\Placeholder::make('formula_note')
                            ->hiddenLabel() // Sembunyikan label biar rapi
                            ->content('Catatan: Jika rumus diisi, kolom ini akan otomatis terkunci (tidak bisa diedit manual) saat input data.')
                            ->extraAttributes(['class' => 'text-xs text-gray-500 italic']), // Styling kecil & miring
                        // ▲▲▲ SELESAI ▲▲▲
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('order') // Fitur Drag & Drop Urutan
            ->defaultSort('order')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kolom')
                    ->searchable()
                    ->description(fn (RecapColumn $record) => $record->parent ? '↳ di dalam ' . $record->parent->name : null),
                
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color('gray'),
                
                TextColumn::make('role')
                    ->label('Peran Dashboard')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'metric_sum' => 'success', // Hijau biar kelihatan "Uang/Angka"
                        'dimension' => 'info',     // Biru untuk Kategori
                        'none' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'metric_sum' => 'Nilai Utama',
                        'dimension' => 'Kategori',
                        'none' => '-',
                    }),

                TextColumn::make('order')->label('#'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Kolom Baru')
                    ->modalWidth('lg') // Modal agak lebar biar enak
                    ->slideOver(),     // Tampil dari samping (lebih modern)
            ])
            ->actions([
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}