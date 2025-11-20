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
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Facades\Filament;

class RecapResource extends Resource
{
    protected static ?string $model = Recap::class;
    protected static ?string $modelLabel = 'Rekapitulasi'; 
    protected static ?string $recordTitleAttribute = 'name'; 

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static bool $shouldRegisterNavigation = false;
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('recapType.project', function ($query) {
            $query->where('company_id', Filament::getTenant()->id);
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // [BERSIH] Section "Informasi Periode" dihapus sesuai permintaan.
                // Halaman Create akan kosong, tapi halaman View (Dashboard) jadi bersih.
                
                // Jika nanti Ndan butuh form ini lagi khusus untuk halaman Create,
                // Kita bisa kembalikan dengan kondisi ->visible(fn ($operation) => $operation === 'create')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([])
            ->actions([
                // Tables\Actions\EditAction::make(), // [HAPUS] Tombol Edit di tabel depan juga kita buang
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
            // 'index' => Pages\ListRecaps::route('/'), // Sudah dihapus sebelumnya
            
            'create' => Pages\CreateRecap::route('/create'),
            'view' => Pages\ViewRecap::route('/{record}'),
            
            // [HAPUS] Halaman Edit dinonaktifkan
            // 'edit' => Pages\EditRecap::route('/{record}/edit'),
        ];
    }
}