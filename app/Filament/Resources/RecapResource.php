<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecapResource\Pages;
use App\Filament\Resources\RecapResource\RelationManagers\RecapRowsRelationManager;
use App\Models\Recap;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Section; // Import Section
use Filament\Facades\Filament; // Import Filament Facade

class RecapResource extends Resource
{
    protected static ?string $model = Recap::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

    // ▼▼▼ 1. MATIKAN SCOPE OTOMATIS (Sama seperti RecapType) ▼▼▼
    protected static bool $isScopedToTenant = false;

    // ▼▼▼ 2. GANTI DENGAN FILTER MANUAL (Agar Data Aman) ▼▼▼
    public static function getEloquentQuery(): Builder
    {
        // Filter: Hanya tampilkan Recap yang RecapType -> Project-nya milik Company ini
        return parent::getEloquentQuery()->whereHas('recapType.project', function ($query) {
            $query->where('company_id', Filament::getTenant()->id);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ▼▼▼ 3. BUNGKUS DALAM SECTION COLLAPSIBLE ▼▼▼
                Section::make('Informasi Periode')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Periode')
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    // Tutup otomatis saat mode 'view' agar user fokus ke tabel data
                    ->collapsed(fn (string $operation) => $operation === 'view'),
                // ▲▲▲ SELESAI BUNGKUS ▲▲▲
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kita kosongkan karena list diakses lewat RelationManager
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
            RecapRowsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecaps::route('/'),
            'create' => Pages\CreateRecap::route('/create'),
            
            // ▼▼▼ 4. DAFTARKAN HALAMAN VIEW ▼▼▼
            'view' => Pages\ViewRecap::route('/{record}'),
            
            'edit' => Pages\EditRecap::route('/{record}/edit'),
        ];
    }
}