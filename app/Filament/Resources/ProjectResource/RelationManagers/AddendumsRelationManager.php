<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;

class AddendumsRelationManager extends RelationManager
{
    protected static string $relationship = 'addendums';

    // GANTI JUDUL TAB DI SINI
    protected static ?string $title = 'Minutes of Meeting (MoM)'; 

    // Biar tombol muncul di Dashboard View
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Judul / Perihal Meeting')
                    ->placeholder('Contoh: Meeting Klarifikasi Spek')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                DatePicker::make('date')
                    ->label('Tanggal Meeting')
                    ->default(now())
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Upload Hasil Scan (PDF/Gambar)')
                    ->directory('addendum-scans')
                    ->disk('public')
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Catatan Singkat (Opsional)')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Perihal')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('file_path')
                    ->label('Status Dokumen')
                    ->formatStateUsing(fn ($state) => $state ? '✅ Terlampir' : 'Menunggu Scan')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Buat Berita Acara Baru'), // Ganti Label Tombol
            ])
            ->actions([
                // 1. TOMBOL CETAK FORMULIR
                Action::make('print_form')
                    ->label('Cetak Form')
                    ->icon('heroicon-o-printer')
                    ->color('warning') 
                    ->url(fn ($record) => route('addendum.print', $record))
                    ->openUrlInNewTab(),

                // 2. TOMBOL LIHAT SCAN
                Action::make('view_scan')
                    ->label('Lihat Scan')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('success')
                    ->url(fn ($record) => Storage::url($record->file_path))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->file_path),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}