<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers\FrameworkAgreementsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\PurchaseOrdersRelationManager;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// IMPORT BARU YANG DIBUTUHKAN
use Filament\Facades\Filament;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn; // Untuk status

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Proyek';

    // ==========================================================
    // ▼▼▼ KUNCI-nya di sini agar posisi-nya di bawah Dasbor ▼▼▼
    // ==========================================================
    protected static ?int $navigationSort = -2; // Dasbor punya -2, jadi -1 ada di bawahnya
    // ==========================================================


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Proyek')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Proyek')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        // ▼▼▼ Ambil daftar Klien dari Tenant saat ini ▼▼▼
                        Select::make('client_id')
                            ->label('Klien')
                            ->options(fn () => Filament::getTenant()->clients()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('status')
                            ->label('Status Proyek')
                            ->options([
                                'Baru' => 'Baru',
                                'Berjalan' => 'Berjalan',
                                'Selesai' => 'Selesai',
                                'Dibatalkan' => 'Dibatalkan',
                            ])
                            ->default('Baru')
                            ->required(),

                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai'),

                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai'),

                        Textarea::make('description')
                            ->label('Deskripsi Proyek')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Proyek')
                    ->searchable()
                    ->sortable(),

                // Tampilkan nama klien
                TextColumn::make('client.name')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),

                // ▼▼▼ Buat status jadi lebih bagus dengan 'Badge' ▼▼▼
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'Baru',
                        'warning' => 'Berjalan',
                        'success' => 'Selesai',
                        'danger' => 'Dibatalkan',
                    ])
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            PurchaseOrdersRelationManager::class,
            FrameworkAgreementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }    
}