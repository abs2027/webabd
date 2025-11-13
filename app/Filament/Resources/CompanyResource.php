<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    // INI ADALAH PERBAIKAN UNTUK ERROR SERVER ANDA
    public static bool $isScopedToTenant = false; 

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // INI ADALAH FORM KOP SURAT ANDA
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->disabled() 
                    ->unique(ignoreRecord: true),

                FileUpload::make('logo_path')
                    ->label('Logo Perusahaan')
                    ->image() 
                    ->directory('company-logos') 
                    ->columnSpanFull(), 

                Textarea::make('address')
                    ->label('Alamat Perusahaan')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel(), 

                TextInput::make('email')
                    ->label('Email Perusahaan')
                    ->email(), 
            ]);
    }

    // INI ADALAH TABEL ANDA
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone') 
                    ->label('Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email') 
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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