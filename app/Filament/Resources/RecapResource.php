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
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecapResource extends Resource
{
    protected static ?string $model = Recap::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;

    // ▼▼▼ PERUBAHAN FLOW DI SINI ▼▼▼

    // 1. HAPUS ATAU KOMENTARI BARIS INI
    // protected static ?string $tenantOwnershipRelationshipName = 'project.company';

    // 2. TAMBAHKAN BARIS INI UNTUK MEMATIKAN PENGECEKAN
    protected static bool $isScopedToTenant = false;

    // ▲▲▲ SELESAI ▲▲▲


    public static function form(Form $form): Form
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            // 'index' => Pages\ListRecaps::route('/'), // <-- HAPUS BARIS INI
            'create' => Pages\CreateRecap::route('/create'), // Biarkan (walau tidak terpakai)
            'edit' => Pages\EditRecap::route('/{record}/edit'),
        ];
    }
}