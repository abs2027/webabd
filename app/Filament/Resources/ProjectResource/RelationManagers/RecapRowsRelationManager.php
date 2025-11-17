<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// IMPORT LENGKAP
use App\Models\RecapColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn; // Jika Anda perlu tipe 'file'
use Illuminate\Database\Eloquent\Model;

class RecapRowsRelationManager extends RelationManager
{
    protected static string $relationship = 'recapRows';
    protected static ?string $title = '2. Data Rekapitulasi';

    /**
     * Fungsi ini akan membuat FORM input secara dinamis
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema(function (): array {
                // 1. Ambil project saat ini
                $project = $this->getOwnerRecord();
                // 2. Ambil kolom-kolom yang sudah didesain
                $columns = $project->recapColumns;
                
                $schema = []; // Ini adalah array untuk form kita
                
                // 3. Loop dan buat field input berdasarkan tipenya
                foreach ($columns as $column) {
                    $field = null;
                    $key = 'data.' . $column->name; // Penting: 'data.' menunjuk ke JSON

                    switch ($column->type) {
                        case 'text':
                            $field = TextInput::make($key);
                            break;
                        case 'number':
                            $field = TextInput::make($key)->numeric();
                            break;
                        case 'date':
                            $field = DatePicker::make($key);
                            break;
                        case 'file':
                            $field = FileUpload::make($key)
                                ->directory('recap-data-files')
                                ->disk('public');
                            break;
                    }
                    
                    if ($field) {
                        $schema[] = $field->label($column->name);
                    }
                }
                
                return $schema; // Kembalikan schema yang sudah dinamis
            });
    }

    /**
     * Fungsi ini akan membuat TABEL TAMPILAN secara dinamis
     */
    public function table(Table $table): Table
    {
        // 1. Ambil project dan kolom-kolomnya
        $project = $this->getOwnerRecord();
        $columns = $project->recapColumns;
        
        $tableColumns = []; // Array untuk kolom tabel
        
        // 2. Loop dan buat kolom tabel
        foreach ($columns as $column) {
            $tableColumn = null;
            $key = 'data.' . $column->name;

            switch ($column->type) {
                case 'file':
                    // Ini contoh, Anda bisa ganti jadi link download jika perlu
                    $tableColumn = TextColumn::make($key) 
                                    ->label($column->name)
                                    ->formatStateUsing(fn ($state) => $state ? "Lihat File" : "-");
                    break;
                default:
                    $tableColumn = TextColumn::make($key)->label($column->name);
                    break;
            }
            
            $tableColumns[] = $tableColumn;
        }

        return $table
            ->columns($tableColumns) // Gunakan kolom dinamis
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}