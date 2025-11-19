<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// IMPORT BARU
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea; 
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Toggle; // <-- IMPORT TOGGLE
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
                TextInput::make('name')
                    ->label('Nama Kolom')
                    ->required()
                    ->placeholder('Misal: Conferma, QTY, Menu Harian'),

                Select::make('type')
                    ->label('Tipe Kolom')
                    ->options([
                        'text' => 'Teks Singkat',
                        'number' => 'Angka (Qty, Nomor)',
                        'money' => 'Uang (Rp)', 
                        'select' => 'Pilihan (Dropdown)', 
                        'date' => 'Tanggal',
                        'file' => 'Upload File',
                        'group' => 'Grup (Induk Saja)'
                    ])
                    ->required()
                    ->live(), 

                Select::make('parent_id')
                    ->label('Kolom Induk (Jika ini turunan)')
                    ->searchable()
                    ->placeholder('Ini adalah kolom induk (top-level)')
                    ->options(function () {
                        $project = $this->getOwnerRecord();
                        if (!$project) {
                            return [];
                        }
                        
                        return $project->recapColumns()
                            ->where('type', 'group') 
                            ->pluck('name', 'id');
                    }),

                TextInput::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),
                
                // ▼▼▼ FITUR BARU: SUMMARY TOGGLE ▼▼▼
                Toggle::make('is_summarized')
                    ->label('Tampilkan Total (Sum) di Footer?')
                    ->inline(false)
                    // Hanya muncul untuk kolom Angka atau Uang
                    ->visible(fn (Get $get) => in_array($get('type'), ['number', 'money'])),
                // ▲▲▲ SELESAI ▲▲▲

                Textarea::make('options')
                    ->label('Opsi Pilihan (Pisahkan dengan Koma)')
                    ->placeholder('Contoh: Lokasi A, Lokasi B, Lokasi C')
                    ->visible(fn (Get $get) => $get('type') === 'select')
                    ->required(fn (Get $get) => $get('type') === 'select')
                    ->columnSpanFull(),

                Fieldset::make('Logika Kalkulasi Otomatis')
                    ->schema([
                        TextInput::make('operand_a')
                            ->label('Nama Kolom A')
                            ->placeholder('Misal: Orderan'),
                        Select::make('operator')
                            ->label('Operator Kalkulasi')
                            ->options([
                                '*' => 'Kali (*)',
                                '+' => 'Tambah (+)',
                                '-' => 'Kurang (-)',
                                '/' => 'Bagi (/)',
                            ]),
                        TextInput::make('operand_b')
                            ->label('Nama Kolom B')
                            ->placeholder('Misal: Harga'),
                    ])
                    ->columns(3)
                    ->visible(fn (Get $get) => in_array($get('type'), ['number', 'money'])), 
                    
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nama Kolom')->sortable(),
                TextColumn::make('type')->label('Tipe Kolom')->badge(),
                TextColumn::make('order')->label('Urutan')->sortable(),
            ])
            ->groups([
                Group::make('parent.name')
                    ->label('Kelompok Induk'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}