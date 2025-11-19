<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecapTypeResource\Pages;
use App\Models\RecapType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section; // IMPOR BARU: Untuk membuat section collapsible

// IMPORT RELATION MANAGER LAMA
use App\Filament\Resources\ProjectResource\RelationManagers\RecapColumnsRelationManager;
use App\Filament\Resources\RecapResource\RelationManagers\RecapsRelationManager as ListPeriodeManager;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class RecapTypeResource extends Resource
{
    protected static ?string $model = RecapType::class;

    // Kita sembunyikan dari Sidebar utama, karena aksesnya lewat Project
    protected static bool $shouldRegisterNavigation = false; 
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('project', function ($query) {
            $query->where('company_id', Filament::getTenant()->id);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ▼▼▼ MODIFIKASI 1: Bungkus dalam Section Collapsible ▼▼▼
                Section::make('Informasi Jenis Rekap')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    // Tutup otomatis jika sedang mode 'view' (lihat) agar rapi seperti dashboard
                    ->collapsed(fn (string $operation) => $operation === 'view'),
                // ▲▲▲ SELESAI MODIFIKASI 1 ▲▲▲
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('project.name')->label('Proyek'),
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
            RecapColumnsRelationManager::class, // Tab 1: Desain Tabel
            ListPeriodeManager::class,          // Tab 2: List Periode
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecapTypes::route('/'),
            'create' => Pages\CreateRecapType::route('/create'),
            
            // ▼▼▼ MODIFIKASI 2: Daftarkan Halaman View ▼▼▼
            'view' => Pages\ViewRecapType::route('/{record}'),
            
            'edit' => Pages\EditRecapType::route('/{record}/edit'),
        ];
    }
}