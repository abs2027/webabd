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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class RecapColumnsRelationManager extends RelationManager
{
    protected static string $relationship = 'recapColumns';
    protected static ?string $title = '1. Desain Tabel Rekapitulasi';

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
                        'text' => 'Teks',
                        'number' => 'Angka',
                        'date' => 'Tanggal',
                        'file' => 'Upload File',
                    ])
                    ->required(),
                
                TextInput::make('order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nama Kolom')->sortable(),
                BadgeColumn::make('type')->label('Tipe Kolom'),
                TextColumn::make('order')->label('Urutan')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->reorderable('order'); // <-- Fitur drag-and-drop untuk urutan!
    }
}