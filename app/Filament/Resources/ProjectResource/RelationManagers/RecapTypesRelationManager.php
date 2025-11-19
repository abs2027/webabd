<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Resources\RecapTypeResource; // Pastikan Import ini ada
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action; // Import Action Custom

class RecapTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'recapTypes';

    protected static ?string $title = 'Daftar Jenis Rekapitulasi';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Jenis Rekap')
                    ->placeholder('Contoh: Rekap Solar, Rekap Material, Rekap Lembur')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Jenis Rekap')
                    ->searchable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50),

                Tables\Columns\TextColumn::make('recaps_count')
                    ->counts('recaps')
                    ->label('Jml. Periode')
                    ->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Jenis Rekap Baru'),
            ])
            ->actions([
                // ▼▼▼ TOMBOL SAKTI: MASUK KE DALAM FOLDER ▼▼▼
                Action::make('manage')
                    ->label('Kelola Desain & Data')
                    ->icon('heroicon-o-folder-open')
                    ->color('primary')
                    // ▼▼▼ UBAH URL KE 'view' ▼▼▼
                    ->url(fn ($record) => RecapTypeResource::getUrl('view', ['record' => $record])),
                // ▲▲▲ ▲▲▲

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