<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\CompanyResource\Pages;
use App\Filament\Superadmin\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ===========================================
                // KODE BARU UNTUK FORM
                // ===========================================
                Section::make('Informasi Perusahaan')
                    ->description('Detail utama untuk perusahaan (tenant).')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true) // Update field lain saat fokus hilang
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))), // Auto-generate slug

                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->required()
                            ->maxLength(255)
                            ->disabled() // Dibuat disabled agar user tidak salah edit
                            ->dehydrated() // Pastikan tetap tersimpan
                            ->unique(Company::class, 'slug', ignoreRecord: true), // Cek unik
                    ])
                    ->columns(2), // Tampilkan 2 kolom
                // ===========================================
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ===========================================
                // TAMBAHKAN KODE INI
                // ===========================================
                TextColumn::make('name')
                    ->label('Nama Perusahaan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                // ===========================================
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
