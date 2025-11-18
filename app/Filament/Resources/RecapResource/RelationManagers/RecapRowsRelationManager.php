<?php

namespace App\Filament\Resources\RecapResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

// IMPORT BARU
use App\Models\RecapColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select; 
use Filament\Forms\Components\Section; 
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum; 
use Filament\Forms\Get; 
use Filament\Forms\Set; 
use Illuminate\Support\Facades\DB; 

class RecapRowsRelationManager extends RelationManager
{
    protected static string $relationship = 'recapRows';
    protected static ?string $title = '2. Data Rekaplitulasi';

    public function form(Form $form): Form
    {
        return $form
            ->schema(function (): array {
                $recap = $this->getOwnerRecord();
                if (!$recap || !$recap->project) {
                    return [];
                }
                $project = $recap->project;
                $parentColumns = $project->recapColumns()
                                        ->whereNull('parent_id')
                                        ->orderBy('order')
                                        ->get();
                return $this->buildSchema($parentColumns, 'data'); 
            });
    }

    protected function buildSchema(iterable $columns, string $baseKey): array
    {
        $schema = [];
        foreach ($columns as $column) {
            $children = $column->children()->orderBy('order')->get();
            $currentKey = $baseKey . '.' . $column->name; 
            if ($column->type == 'group' && $children->isNotEmpty()) {
                $childSchema = $this->buildSchema($children, $currentKey); 
                $schema[] = Section::make($column->name) 
                                ->label($column->name)
                                ->schema($childSchema)
                                ->columns(2); 
            } else if ($column->type != 'group') {
                $field = null;
                switch ($column->type) {
                    case 'text': $field = TextInput::make($currentKey); break;
                    case 'select':
                        $options = $column->options ? array_map('trim', explode(',', $column->options)) : [];
                        $optionsArray = array_combine($options, $options);
                        $field = Select::make($currentKey)->options($optionsArray)->searchable();
                        break;
                    case 'number': $field = TextInput::make($currentKey)->numeric(); break;
                    case 'money': $field = TextInput::make($currentKey)->numeric()->prefix('Rp'); break;
                    case 'date': $field = DatePicker::make($currentKey); break;
                    case 'file': $field = FileUpload::make($currentKey)->directory('recap-data-files')->disk('public'); break;
                }
                if (in_array($column->type, ['number', 'money', 'select'])) {
                    $cleanNumber = function ($val) {
                        if (is_numeric($val)) return (float) $val;
                        $val = str_replace(['Rp', '.', ' '], '', $val);
                        $val = str_replace(',', '.', $val); 
                        return (float) $val;
                    };
                    if ($column->operand_a && $column->operator && $column->operand_b) {
                            $basePath = substr($currentKey, 0, strrpos($currentKey, '.'));
                            $pathA = $basePath . '.' . $column->operand_a;
                            $pathB = $basePath . '.' . $column->operand_b;
                            $field->disabled()->dehydrated()
                            ->default(function (Get $get) use ($column, $pathA, $pathB) { return 0; })
                            ->formatStateUsing(function ($state, Get $get) use ($column, $pathA, $pathB, $cleanNumber) {
                                if ($state) return $state;
                                $valA = $cleanNumber($get($pathA));
                                $valB = $cleanNumber($get($pathB));
                                switch ($column->operator) {
                                    case '*': return $valA * $valB;
                                    case '+': return $valA + $valB;
                                    case '-': return $valA - $valB;
                                    case '/': return ($valB != 0) ? $valA / $valB : 0;
                                    default: return 0;
                                }
                            });
                    }
                    $isUsed = RecapColumn::where('operand_a', $column->name)->orWhere('operand_b', $column->name)->exists();
                    if ($isUsed) {
                        $isSelect = $column->type === 'select';
                        $field->live(onBlur: !$isSelect)->afterStateUpdated(function (Get $get, Set $set) use ($currentKey, $cleanNumber) {
                                $parts = explode('.', $currentKey);
                                $colName = array_pop($parts); 
                                $basePath = implode('.', $parts); 
                                $targets = RecapColumn::where('operand_a', $colName)->orWhere('operand_b', $colName)->get();
                                foreach($targets as $target) {
                                     $targetPath = $basePath . '.' . $target->name;
                                     $pathA = $basePath . '.' . $target->operand_a;
                                     $pathB = $basePath . '.' . $target->operand_b;
                                     $valA = $cleanNumber($get($pathA));
                                     $valB = $cleanNumber($get($pathB));
                                     $res = 0;
                                     switch ($target->operator) {
                                         case '*': $res = $valA * $valB; break;
                                         case '+': $res = $valA + $valB; break;
                                         case '-': $res = $valA - $valB; break;
                                         case '/': $res = ($valB != 0) ? $valA / $valB : 0; break;
                                     }
                                     $set($targetPath, $res);
                                }
                            });
                    }
                }
                if ($field) { $schema[] = $field->label($column->name); }
            }
        }
        return $schema;
    }

    public function table(Table $table): Table
    {
        $recap = $this->getOwnerRecord();
        if (!$recap || !$recap->project) {
            return $table->columns([]); 
        }
        $project = $recap->project;
        $tableColumns = [];
        $dataColumns = $project->recapColumns()
                              ->where('type', '!=', 'group')
                              ->orderBy('order')
                              ->get();

        foreach ($dataColumns as $column) {
            $key = 'data.';
            $path = [];
            $tempCol = $column;
            while ($tempCol != null) {
                array_unshift($path, $tempCol->name);
                $tempCol = $tempCol->parent;
            }
            $key .= implode('.', $path);
            
            $tableColumn = null;
            switch ($column->type) {
                case 'file':
                    $tableColumn = TextColumn::make($key)
                                    ->label($column->name)
                                    ->formatStateUsing(fn ($state) => $state ? "Lihat File" : "-");
                    break;
                
                case 'money':
                    $tableColumn = TextColumn::make($key)
                                    ->label($column->name)
                                    ->money('IDR', true);
                    
                    if ($column->is_summarized) {
                        $tableColumn->summarize(
                            Sum::make()
                                ->money('IDR', true)
                                // ▼▼▼ PERBAIKAN LABEL DI SINI ▼▼▼
                                ->label('Total ' . $column->name) 
                                // ▲▲▲ SELESAI ▲▲▲
                                ->using(function ($query) use ($key) {
                                    $path = substr($key, 5); 
                                    $segments = collect(explode('.', $path))
                                        ->map(fn($segment) => '"' . $segment . '"')
                                        ->join('.');
                                    
                                    return $query->sum(
                                        DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$." . $segments . "')) AS DECIMAL(15, 2))")
                                    );
                                })
                        );
                    }
                    break;
                
                case 'number':
                     $tableColumn = TextColumn::make($key)->label($column->name)->numeric();
                     
                     if ($column->is_summarized) {
                        $tableColumn->summarize(
                            Sum::make()
                                // ▼▼▼ PERBAIKAN LABEL DI SINI ▼▼▼
                                ->label('Total ' . $column->name)
                                // ▲▲▲ SELESAI ▲▲▲
                                ->using(function ($query) use ($key) {
                                    $path = substr($key, 5); 
                                    $segments = collect(explode('.', $path))
                                        ->map(fn($segment) => '"' . $segment . '"')
                                        ->join('.');
                                    
                                    return $query->sum(
                                        DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$." . $segments . "')) AS DECIMAL(15, 2))")
                                    );
                                })
                        );
                     }
                     break;

                default:
                    $tableColumn = TextColumn::make($key)->label($column->name);
                    break;
            }
            $tableColumns[] = $tableColumn;
        }
        return $table
            ->columns($tableColumns)
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