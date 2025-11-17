<?php

namespace App\Filament\Resources\RecapResource\RelationManagers;

use App\Filament\Resources\RecapResource;
use App\Models\Recap;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecapsRelationManager extends RelationManager
{
    protected static string $relationship = 'recaps';
    protected static ?string $title = '2. Periode Rekapitulasi';

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
            Tables\Columns\TextColumn::make('name')->label('Nama Periode'),
            Tables\Columns\TextColumn::make('start_date')->label('Tgl. Mulai')->date(),
            Tables\Columns\TextColumn::make('end_date')->label('Tgl. Selesai')->date(),
        ])
        ->headerActions([
            Tables\Actions\CreateAction::make(),
        ])
        ->actions([
            // ▼▼▼ INI TOMBOL KUNCI-NYA ▼▼▼
            // Tombol untuk "masuk" ke halaman input data
            Tables\Actions\Action::make('Input Data')
                ->url(fn (Recap $record): string => RecapResource::getUrl('edit', ['record' => $record]))
                ->icon('heroicon-o-pencil-square'),

            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }
}
