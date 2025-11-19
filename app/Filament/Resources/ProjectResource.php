<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\AddendumsRelationManager;
// ... imports relation managers ...
use App\Filament\Resources\ProjectResource\RelationManagers\FrameworkAgreementsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\PurchaseOrdersRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\RecapTypesRelationManager;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn; 

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Proyek';
    protected static ?int $navigationSort = -2; 

    public static function form(Form $form): Form
    {
        // Form tetap sama seperti sebelumnya (Collapsible)
        // Di halaman View, form ini akan otomatis terlihat "Disabled" (Read-only)
        return $form
            ->schema([
                Section::make('Informasi Kontrak Proyek')
                    ->schema([
                        TextInput::make('name')->label('Nama Proyek')->required(),
                        Select::make('client_id')->label('Klien')
                            ->options(fn () => Filament::getTenant()->clients()->pluck('name', 'id'))
                            ->required(),
                        Select::make('status')->label('Status Proyek')
                            ->options(['Baru' => 'Baru', 'Berjalan' => 'Berjalan', 'Selesai' => 'Selesai', 'Dibatalkan' => 'Dibatalkan'])
                            ->default('Baru')->required(),
                        TextInput::make('payment_term_value')->label('Termin Pembayaran')->numeric(),
                        Select::make('payment_term_unit')->label('Satuan Termin')
                            ->options(['days' => 'Hari', 'months' => 'Bulan', 'years' => 'Tahun']),
                        DatePicker::make('start_date')->label('Tanggal Mulai'),
                        DatePicker::make('end_date')->label('Tanggal Selesai'),
                        Textarea::make('description')->label('Deskripsi Proyek')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(), // Tetap collapsible biar rapi
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Proyek')->searchable()->sortable(),
                TextColumn::make('client.name')->label('Klien')->searchable()->sortable(),
                BadgeColumn::make('status')->label('Status')
                    ->colors(['primary' => 'Baru', 'warning' => 'Berjalan', 'success' => 'Selesai', 'danger' => 'Dibatalkan'])
                    ->sortable(),
                TextColumn::make('end_date')->label('Tanggal Selesai')->date()->sortable(),
            ])
            ->actions([
                // ▼▼▼ PERUBAHAN DI SINI ▼▼▼
                
                // 1. Tombol "KELOLA" -> Arahkan ke ViewProject
                Tables\Actions\Action::make('manage')
                    ->label('Kelola')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('primary')
                    // URL-nya kita arahkan ke halaman VIEW
                    ->url(fn (Project $record): string => Pages\ViewProject::getUrl(['record' => $record])),
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
            FrameworkAgreementsRelationManager::class,
            AddendumsRelationManager::class,
            PurchaseOrdersRelationManager::class,
            RecapTypesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            
            // ▼▼▼ DAFTARKAN HALAMAN VIEW DI SINI ▼▼▼
            'view' => Pages\ViewProject::route('/{record}'), 
            
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }    
}