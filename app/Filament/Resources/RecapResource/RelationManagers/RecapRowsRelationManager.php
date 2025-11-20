<?php

namespace App\Filament\Resources\RecapResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\RecapColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select; 
use Filament\Forms\Components\Section; 
use Filament\Forms\Components\Placeholder; 
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Grouping\Group; 
use Filament\Forms\Get; 
use Filament\Forms\Set; 
use Illuminate\Support\Facades\DB; 
use Filament\Tables\Actions\Action; 
use Filament\Tables\Actions\ActionGroup; 
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString; 
use Illuminate\Support\Arr; 
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Database\Eloquent\Model; 

class RecapRowsRelationManager extends RelationManager
{
    protected static string $relationship = 'recapRows';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Detail Rekapitulasi - ' . $ownerRecord->name;
    }

    public function isReadOnly(): bool
    {
        return false;
    }
    
    public function copyFromHistory($rowId)
    {
        $record = $this->getOwnerRecord()->recapRows()->find($rowId);
        if (!$record) {
            Notification::make()->title('Data tidak ditemukan')->danger()->send();
            return;
        }
        $this->getMountedTableActionForm()->fill(['data' => $record->data]);
        Notification::make()->title('Data disalin dari riwayat!')->success()->duration(2000)->send();
    }

    public function form(Form $form): Form
    {
        $recap = $this->getOwnerRecord();
        if (!$recap || !$recap->recapType) { return $form->schema([]); }
        $recapType = $recap->recapType;
        $parentColumns = $recapType->recapColumns()->whereNull('parent_id')->orderBy('order')->get();
        $formFields = $this->buildSchema($parentColumns, 'data'); 

        $cheatSheetSection = Section::make('Riwayat Input Data')
            ->description('Klik untuk membuka/menutup riwayat data terakhir.')
            ->icon('heroicon-o-clock') 
            ->collapsible() 
            ->collapsed()   
            ->compact()     
            ->columnSpanFull()
            ->visible(fn ($operation) => $operation === 'create')
            ->schema([
                Placeholder::make('latest_data_content')
                    ->hiddenLabel() 
                    ->content(function () use ($recap, $recapType) {
                        $previewColumns = $recapType->recapColumns()
                            ->where('type', '!=', 'group')
                            ->orderBy('order')
                            ->get();
                        $latestRows = $recap->recapRows()->latest()->take(5)->get()->reverse();
                        if ($latestRows->isEmpty()) {
                            return new HtmlString('<div class="text-xs text-gray-500 italic text-center p-2">Belum ada data masuk.</div>');
                        }
                        $headerHtml = '';
                        foreach ($previewColumns as $col) {
                            $headerHtml .= "<th class='px-3 py-2 text-left whitespace-nowrap bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-700 sticky top-0 z-10'>{$col->name}</th>";
                        }
                        $rowsHtml = '';
                        foreach ($latestRows as $row) {
                            $tds = '';
                            foreach ($previewColumns as $col) {
                                $flatData = Arr::dot($row->data ?? []);
                                $value = '-';
                                foreach ($flatData as $k => $v) {
                                    if (str_ends_with($k, $col->name)) {
                                        $value = $v;
                                        if ($col->type == 'money') $value = number_format((float)str_replace(['.',','],['','.'],$value), 0, ',', '.');
                                        break;
                                    }
                                }
                                $tds .= "<td class='px-3 py-1 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap'>{$value}</td>";
                            }
                            $rowsHtml .= "<tr wire:click=\"copyFromHistory('{$row->id}')\" class='text-xs hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors cursor-pointer group' title='Klik untuk menyalin data baris ini'>{$tds}</tr>";
                        }
                        return new HtmlString("<div class='overflow-auto max-h-44 rounded border border-gray-200 dark:border-gray-700 shadow-sm scrollbar-thin'><table class='w-full text-xs text-gray-600 dark:text-gray-400 border-collapse'><thead class='font-bold text-gray-700 dark:text-gray-200'><tr>{$headerHtml}</tr></thead><tbody>{$rowsHtml}</tbody></table><div class='text-[10px] text-gray-400 text-center py-1 italic bg-gray-50 dark:bg-gray-800 border-t dark:border-gray-700'>* Tips: Klik salah satu baris di atas untuk menyalin isinya.</div></div>");
                    })
            ]);
        array_unshift($formFields, $cheatSheetSection);
        return $form->schema($formFields);
    }

    protected function buildSchema(iterable $columns, string $baseKey): array
    {
        $schema = [];
        foreach ($columns as $column) {
            $children = $column->children()->orderBy('order')->get();
            $currentKey = $baseKey . '.' . $column->name; 
            
            if ($column->type == 'group' && $children->isNotEmpty()) {
                $childSchema = $this->buildSchema($children, $currentKey); 
                $schema[] = Section::make($column->name)->label($column->name)->schema($childSchema)->columns(2); 
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
                    
                    case 'date': 
                        $field = DatePicker::make($currentKey)
                            ->native(false) 
                            ->minDate(fn () => $this->getOwnerRecord()->start_date)
                            ->maxDate(fn () => $this->getOwnerRecord()->end_date)
                            ->validationMessages([
                                'after_or_equal' => 'Tanggal tidak boleh mendahului Tanggal Mulai Periode.',
                                'before_or_equal' => 'Tanggal tidak boleh melebihi Tanggal Selesai Periode.',
                            ])
                            ->helperText(function () {
                                $start = $this->getOwnerRecord()->start_date?->format('d M');
                                $end = $this->getOwnerRecord()->end_date?->format('d M');
                                return $start && $end ? "Pilih tanggal antara $start - $end" : null;
                            });
                        break;

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
                        $field->disabled()->dehydrated()->default(fn()=>0)
                        ->formatStateUsing(function ($state, Get $get) use ($column, $pathA, $pathB, $cleanNumber) {
                            if ($state) return $state;
                            $valA = $cleanNumber($get($pathA)); $valB = $cleanNumber($get($pathB));
                            switch ($column->operator) { case '*': return $valA * $valB; case '+': return $valA + $valB; case '-': return $valA - $valB; case '/': return ($valB != 0) ? $valA / $valB : 0; default: return 0; }
                        });
                    }
                    $isUsed = RecapColumn::where('operand_a', $column->name)->orWhere('operand_b', $column->name)->exists();
                    if ($isUsed) {
                        $isSelect = $column->type === 'select';
                        $field->live(debounce: $isSelect ? null : 500)
                              ->afterStateUpdated(function (Get $get, Set $set) use ($currentKey, $cleanNumber) {
                             $parts = explode('.', $currentKey); $colName = array_pop($parts); $basePath = implode('.', $parts); 
                             $targets = RecapColumn::where('operand_a', $colName)->orWhere('operand_b', $colName)->get();
                             foreach($targets as $target) {
                                  $targetPath = $basePath . '.' . $target->name;
                                  $pathA = $basePath . '.' . $target->operand_a; $pathB = $basePath . '.' . $target->operand_b;
                                  $valA = $cleanNumber($get($pathA)); $valB = $cleanNumber($get($pathB));
                                  $res = 0;
                                  switch ($target->operator) { case '*': $res = $valA * $valB; break; case '+': $res = $valA + $valB; break; case '-': $res = $valA - $valB; break; case '/': $res = ($valB != 0) ? $valA / $valB : 0; break; }
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
        if (!$recap || !$recap->recapType) {
            return $table->columns([]); 
        }

        $recapType = $recap->recapType;
        $dataColumns = $recapType->recapColumns()->where('type', '!=', 'group')->orderBy('order')->get();

        $groups = [];
        $groupCandidates = $recapType->recapColumns()
            ->whereIn('type', ['select', 'date'])
            ->orderBy('order')
            ->get();

        foreach ($groupCandidates as $col) {
            $path = []; 
            $tempCol = $col;
            while ($tempCol != null) { 
                array_unshift($path, $tempCol->name); 
                $tempCol = $tempCol->parent; 
            }
            $quotedPathStr = collect($path)->map(fn($s) => '"' . $s . '"')->join('.');
            $jsonPath = "data->'$." . $quotedPathStr . "'";
            $dotPath = implode('.', $path);

            $groups[] = Group::make('group_col_' . $col->id)
                ->label($col->name)
                ->getTitleFromRecordUsing(function ($record) use ($dotPath) {
                    return Arr::get($record->data, $dotPath) ?? '-';
                })
                ->scopeQueryUsing(function (Builder $query) use ($jsonPath) {
                    return $query->orderByRaw("JSON_UNQUOTE($jsonPath)");
                })
                ->orderQueryUsing(function (Builder $query, ?string $direction = 'asc') use ($jsonPath) {
                    $direction = $direction ?? 'asc';
                    $query->orderByRaw("JSON_UNQUOTE($jsonPath) $direction");
                })
                ->collapsible();
        }

        $tableColumns = [];
        foreach ($dataColumns as $column) {
            $key = 'data.';
            $path = [];
            $tempCol = $column;
            while ($tempCol != null) {
                array_unshift($path, $tempCol->name);
                $tempCol = $tempCol->parent;
            }
            $key .= implode('.', $path);
            
            $quotedPath = collect($path)
                ->map(fn($segment) => '"' . $segment . '"')
                ->join('.');
            $jsonPath = "data->'$." . $quotedPath . "'"; 

            $tableColumn = null;
            switch ($column->type) {
                case 'file':
                    $tableColumn = TextColumn::make($key)
                                    ->label($column->name)
                                    ->formatStateUsing(fn ($state) => $state ? "Lihat File" : "-")
                                    ->icon('heroicon-o-document');
                    break;
                
                case 'money':
                    $tableColumn = TextColumn::make($key)->label($column->name)->money('IDR', true);
                    if ($column->is_summarized) {
                        $tableColumn->summarize(
                            Sum::make()
                                ->money('IDR', true)
                                ->label(stripos($column->name, 'Total') === 0 ? $column->name : 'Total ' . $column->name) 
                                ->using(function ($query) use ($jsonPath) {
                                    return $query->sum(DB::raw("CAST(JSON_UNQUOTE($jsonPath) AS DECIMAL(15, 2))"));
                                })
                        );
                    }
                    break;

                case 'number':
                     $tableColumn = TextColumn::make($key)->label($column->name)->numeric();
                     if ($column->is_summarized) {
                        $tableColumn->summarize(
                            Sum::make()
                                ->label(stripos($column->name, 'Total') === 0 ? $column->name : 'Total ' . $column->name)
                                ->using(function ($query) use ($jsonPath) {
                                    return $query->sum(DB::raw("CAST(JSON_UNQUOTE($jsonPath) AS DECIMAL(15, 2))"));
                                })
                        );
                     }
                     break;
                
                case 'select':
                    $tableColumn = TextColumn::make($key)->label($column->name);
                    if ($column->is_summarized) {
                        $tableColumn->summarize(
                            Sum::make()
                                ->label(stripos($column->name, 'Total') === 0 ? $column->name : 'Total ' . $column->name)
                                ->using(function ($query) use ($jsonPath) {
                                    return $query->sum(DB::raw("CAST(JSON_UNQUOTE($jsonPath) AS DECIMAL(15, 2))"));
                                })
                        );
                    }
                    $tableColumn->searchable(query: function ($query, string $search) use ($jsonPath) {
                        $query->whereRaw("LOWER(JSON_UNQUOTE($jsonPath)) LIKE ?", ["%".strtolower($search)."%"]);
                    });
                    break;

                default:
                    $tableColumn = TextColumn::make($key)->label($column->name);
                    $tableColumn->searchable(query: function ($query, string $search) use ($jsonPath) {
                        $query->whereRaw("LOWER(JSON_UNQUOTE($jsonPath)) LIKE ?", ["%".strtolower($search)."%"]);
                    });
                    break;
            }
            
            $tableColumn->toggleable(); 
            $tableColumns[] = $tableColumn;
        }

        $filters = [];
        foreach ($dataColumns as $column) {
            $path = []; $tempCol = $column;
            while ($tempCol != null) { array_unshift($path, $tempCol->name); $tempCol = $tempCol->parent; }
            $quotedPath = collect($path)->map(fn($s) => '"' . $s . '"')->join('.');
            $jsonPath = "data->'$." . $quotedPath . "'";

            if ($column->type === 'select') {
                $options = $column->options ? array_map('trim', explode(',', $column->options)) : [];
                $optionsArray = array_combine($options, $options);
                $filters[] = Tables\Filters\SelectFilter::make('filter_' . $column->id)
                    ->label($column->name)
                    ->options($optionsArray)
                    ->query(fn ($query, array $data) => $data['value'] ? $query->whereRaw("JSON_UNQUOTE($jsonPath) = ?", [$data['value']]) : null);
            }
            if ($column->type === 'date') {
                $filters[] = Tables\Filters\Filter::make('filter_' . $column->id)
                    ->label('Periode ' . $column->name)
                    ->form([ Forms\Components\DatePicker::make('from'), Forms\Components\DatePicker::make('until') ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q, $d) => $q->whereRaw("JSON_UNQUOTE($jsonPath) >= ?", [$d]))
                        ->when($data['until'], fn ($q, $d) => $q->whereRaw("JSON_UNQUOTE($jsonPath) <= ?", [$d]))
                    );
            }
        }

        return $table
            ->striped() 
            ->groups($groups)
            ->columns($tableColumns)
            ->filters($filters)
            ->filtersFormColumns(2) 
            ->filtersFormWidth('4xl')
            
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Submit Rekapitulasi'),

                ActionGroup::make([
                    
                    Action::make('download_template')
                        ->label('Download Template')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('primary') 
                        ->action(function () use ($recap, $dataColumns) {
                            $filename = 'Template-' . Str::slug($recap->name) . '.csv';
                            $headers = $dataColumns->pluck('name')->toArray();
                            return response()->streamDownload(function () use ($headers) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, $headers); 
                                fclose($file);
                            }, $filename);
                        }),
                    
                    Action::make('import_csv')
                        ->label('Upload CSV')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('primary')
                        ->form([
                            FileUpload::make('file')
                                ->label('File CSV')
                                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                                ->required()
                        ])
                        ->action(function (array $data) use ($recap, $recapType) {
                            $path = Storage::disk('public')->path($data['file']);
                            $file = fopen($path, 'r');
                            $headers = fgetcsv($file);
                            if (!$headers) {
                                Notification::make()->title('File CSV kosong')->danger()->send();
                                return;
                            }
                            $allColumns = $recapType->recapColumns()->where('type', '!=', 'group')->get();
                            $columnMap = []; 
                            foreach ($allColumns as $col) {
                                $pathArr = []; $tempCol = $col;
                                while ($tempCol != null) { array_unshift($pathArr, $tempCol->name); $tempCol = $tempCol->parent; }
                                $dotPath = implode('.', $pathArr);
                                $columnMap[strtolower(trim($col->name))] = [ 'path' => $dotPath, 'type' => $col->type ];
                            }
                            $importedCount = 0;
                            while (($row = fgetcsv($file)) !== false) {
                                $rowDataJSON = [];
                                $hasData = false;
                                foreach ($row as $index => $value) {
                                    if (!isset($headers[$index])) continue;
                                    $headerName = strtolower(trim($headers[$index]));
                                    if (isset($columnMap[$headerName])) {
                                        $mapping = $columnMap[$headerName];
                                        $jsonPath = $mapping['path'];
                                        $colType = $mapping['type'];
                                        $cleanVal = trim($value);
                                        if (in_array($colType, ['money', 'number'])) {
                                            $cleanVal = str_ireplace(['Rp', 'IDR', ' '], '', $cleanVal);
                                            if (str_contains($cleanVal, '.') && !str_contains($cleanVal, ',')) {
                                                $cleanVal = str_replace('.', '', $cleanVal);
                                            } elseif (str_contains($cleanVal, ',')) {
                                                $cleanVal = str_replace('.', '', $cleanVal); 
                                                $cleanVal = str_replace(',', '.', $cleanVal); 
                                            }
                                            if (!is_numeric($cleanVal)) $cleanVal = 0;
                                        }
                                        if ($cleanVal !== '') {
                                            Arr::set($rowDataJSON, $jsonPath, $cleanVal);
                                            $hasData = true;
                                        }
                                    }
                                }
                                if ($hasData) {
                                    $recap->recapRows()->create(['data' => $rowDataJSON]);
                                    $importedCount++;
                                }
                            }
                            fclose($file);
                            Notification::make()->title("Sukses! {$importedCount} data diimport.")->success()->send();
                        }),

                    Action::make('export')
                        ->label('Export Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function () use ($recap, $dataColumns) {
                             $headers = $dataColumns->pluck('name')->toArray();
                             array_unshift($headers, 'No'); 
                             $rows = $recap->recapRows()->get(); 
                             $callback = function () use ($headers, $rows, $dataColumns) {
                                 $file = fopen('php://output', 'w');
                                 fputcsv($file, $headers);
                                 foreach ($rows as $index => $row) {
                                     $rowData = [ $index + 1 ];
                                     foreach ($dataColumns as $col) {
                                         $value = $row->data;
                                         $tempCol = $col; $path = [];
                                         while ($tempCol != null) { array_unshift($path, $tempCol->name); $tempCol = $tempCol->parent; }
                                         foreach ($path as $key) { $value = $value[$key] ?? null; }
                                         if (is_array($value)) $value = json_encode($value);
                                         $rowData[] = $value;
                                     }
                                     fputcsv($file, $rowData);
                                 }
                                 fclose($file);
                             };
                             $filename = 'Rekap-' . Str::slug($recap->name) . '.csv';
                             return response()->stream($callback, 200, [
                                 'Content-Type' => 'text/csv',
                                 'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                             ]);
                        }),

                    Action::make('export_pdf')
                        ->label('Print PDF') 
                        ->icon('heroicon-o-printer') 
                        ->color('danger')
                        ->url(fn ($livewire) => route('recap.print', ['record' => $livewire->getOwnerRecord()]))
                        ->openUrlInNewTab(),

                ])
                ->label('Aksi Lainnya')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')         
                ->button(),
            ])
            // ▼▼▼ PERBAIKAN POSISI DI SINI ▼▼▼
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Select')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->extraAttributes(['class' => 'order-1']) // Order 1 = Kiri (Lebih dulu)
            )
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-o-funnel')
                    ->extraAttributes(['class' => 'order-2']) // Order 2 = Kanan (Setelahnya)
            )
            // ▲▲▲ SELESAI PERBAIKAN ▲▲▲
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}