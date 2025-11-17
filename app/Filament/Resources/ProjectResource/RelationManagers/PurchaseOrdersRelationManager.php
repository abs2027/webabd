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

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';
    protected static ?string $title = 'Purchase Orders (PO)';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('po_number')
                    ->label('Nomor PO')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('po_date')
                    ->label('Tanggal PO'),

                FileUpload::make('po_document_path')
                    ->label('Upload Dokumen PO')
                    ->directory('po-documents') // Folder penyimpanan
                    ->disk('public') // Pastikan storage:link
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('po_number')
            ->columns([
                TextColumn::make('po_number')->label('Nomor PO'),
                TextColumn::make('po_date')->label('Tanggal PO')->date(),
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
                    ->url(fn ($record) => Storage::url($record->po_document_path))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->po_document_path),

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