<?php

namespace App\Filament\Superadmin\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompaniesRelationManager extends RelationManager
{
    protected static string $relationship = 'companies';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name') // Kolom 'name' dari tabel 'companies'
            ->columns([
                TextColumn::make('name')->label('Nama Perusahaan'),
                TextColumn::make('slug'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Ini adalah tombol "Hubungkan" (Attach)
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(), // Bikin dropdown-nya bisa di-search
            ])
            ->actions([
                // Ini adalah tombol "Lepaskan" (Detach)
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
