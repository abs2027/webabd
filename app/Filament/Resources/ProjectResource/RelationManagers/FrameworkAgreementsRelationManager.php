<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
// IMPORT TAMBAHAN
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;

class FrameworkAgreementsRelationManager extends RelationManager
{
    protected static string $relationship = 'frameworkAgreements'; // <-- Relasi FA
    protected static ?string $title = 'Framework Agreements (FA)'; // <-- Judul FA

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('fa_number') // <-- Ini FA
                    ->label('Nomor FA')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('fa_date') // <-- Ini FA
                    ->label('Tanggal FA'),

                FileUpload::make('fa_document_path') // <-- Ini FA
                    ->label('Upload Dokumen FA')
                    ->directory('fa-documents') // Folder penyimpanan FA
                    ->disk('public')
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fa_number') // <-- Ini FA
            ->columns([
                TextColumn::make('fa_number')->label('Nomor FA'), // <-- Ini FA
                TextColumn::make('fa_date')->label('Tanggal FA')->date(), // <-- Ini FA
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // TOMBOL DOWNLOAD
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn ($record) => Storage::url($record->fa_document_path)) // <-- Path FA
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->fa_document_path), // <-- Path FA

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