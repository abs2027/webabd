<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\UserResource\Pages;
use App\Filament\Superadmin\Resources\UserResource\RelationManagers;
use App\Filament\Superadmin\Resources\UserResource\RelationManagers\CompaniesRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ===========================================
                // KODE BARU UNTUK FORM
                // ===========================================
                Section::make('Informasi User')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(User::class, 'email', ignoreRecord: true), // Cek unik

                        TextInput::make('password')
                            ->password()
                            // Hash password HANYA jika diisi
                            ->dehydrateStateUsing(fn (?string $state): ?string => 
                                $state ? Hash::make($state) : null
                            )
                            // Hanya simpan ke DB jika diisi
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            // Wajib diisi HANYA saat membuat user baru
                            ->required(fn (string $context): bool => $context === 'create')
                            ->label('Password (biarkan kosong jika tidak ingin mengubah)'),

                        Toggle::make('is_superadmin')
                            ->label('Adalah Superadmin')
                            ->required(),
                    ])->columns(2), // Tampilkan dalam 2 kolom
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
                    ->label('Nama User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_superadmin')
                    ->label('Superadmin')
                    ->boolean(), // Ini akan menampilkan icon centang/silang
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan by default
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
            CompaniesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
