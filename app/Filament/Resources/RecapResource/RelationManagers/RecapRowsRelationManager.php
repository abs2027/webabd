<?php

namespace App\Filament\Resources\RecapResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\RecapColumn;
use App\Helpers\RecapHelper; // Helper aktif
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select; 
use Filament\Forms\Components\Section; 
use Filament\Forms\Components\Placeholder; 
use Filament\Forms\Components\Toggle; 
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
use Carbon\Carbon; // Import Carbon untuk format tanggal

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

        $cheatSheetSection = Section::make('Riwayat Input Terakhir')
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
                            $align = in_array($col->type, ['money', 'number']) ? 'text-right' : 'text-left';
                            $headerHtml .= "<th class='px-3 py-2 {$align} whitespace-nowrap bg-gray-50 dark:bg-gray-800 border-b dark:border-gray-700 sticky top-0 z-10'>{$col->name}</th>";
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
                                        if ($col->type == 'money') $value = number_format(RecapHelper::cleanNumber($value), 0, ',', '.');
                                        break;
                                    }
                                }
                                $align = in_array($col->type, ['money', 'number']) ? 'text-right' : 'text-left';
                                $tds .= "<td class='px-3 py-1 border-b border-gray-200 dark:border-gray-700 whitespace-nowrap {$align}'>{$value}</td>";
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
                            ]);
                        break;
                    case 'file': $field = FileUpload::make($currentKey)->directory('recap-data-files')->disk('public'); break;
                }

                if (in_array($column->type, ['number', 'money', 'select'])) {
                    if ($column->operand_a && $column->operator && $column->operand_b) {
                        $basePath = substr($currentKey, 0, strrpos($currentKey, '.'));
                        $pathA = $basePath . '.' . $column->operand_a;
                        $pathB = $basePath . '.' . $column->operand_b;
                        $field->disabled()->dehydrated()->default(fn()=>0)
                        ->formatStateUsing(function ($state, Get $get) use ($column, $pathA, $pathB) {
                            if ($state) return $state;
                            $valA = RecapHelper::cleanNumber($get($pathA)); 
                            $valB = RecapHelper::cleanNumber($get($pathB));
                            switch ($column->operator) { case '*': return $valA * $valB; case '+': return $valA + $valB; case '-': return $valA - $valB; case '/': return ($valB != 0) ? $valA / $valB : 0; default: return 0; }
                        });
                    }
                    $isUsed = RecapColumn::where('operand_a', $column->name)->orWhere('operand_b', $column->name)->exists();
                    if ($isUsed) {
                        $isSelect = $column->type === 'select';
                        $field->live(debounce: $isSelect ? null : 500)
                              ->afterStateUpdated(function (Get $get, Set $set) use ($currentKey) {
                             $parts = explode('.', $currentKey); $colName = array_pop($parts); $basePath = implode('.', $parts); 
                             $targets = RecapColumn::where('operand_a', $colName)->orWhere('operand_b', $colName)->get();
                             foreach($targets as $target) {
                                  $targetPath = $basePath . '.' . $target->name;
                                  $pathA = $basePath . '.' . $target->operand_a; $pathB = $basePath . '.' . $target->operand_b;
                                  $valA = RecapHelper::cleanNumber($get($pathA)); 
                                  $valB = RecapHelper::cleanNumber($get($pathB));
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
        if (!$recap || !$recap->recapType) { return $table->columns([]); }

        $recapType = $recap->recapType;
        $dataColumns = $recapType->recapColumns()->where('type', '!=', 'group')->orderBy('order')->get();

        $groups = [];
        $groupCandidates = $recapType->recapColumns()->whereIn('type', ['select', 'date'])->orderBy('order')->get();
        foreach ($groupCandidates as $col) {
            $path = []; $tempCol = $col;
            while ($tempCol != null) { array_unshift($path, $tempCol->name); $tempCol = $tempCol->parent; }
            $quotedPathStr = collect($path)->map(fn($s) => '"' . $s . '"')->join('.');
            $jsonPath = "data->'$." . $quotedPathStr . "'";
            $dotPath = implode('.', $path);

            // Syntax SQL JSON
            $jsonColumnPath = str_replace('.', '->', $dotPath);
            $groupSqlKey = "data->{$jsonColumnPath}";
            
            // Cek apakah kolom ini bertipe DATE untuk formatting
            $isDate = $col->type === 'date';

            $groups[] = Group::make($groupSqlKey)
                ->label($col->name)
                ->getKeyFromRecordUsing(fn($record) => (string) (Arr::get($record->data, $dotPath) ?? '-'))
                
                // UPDATE VISUAL: Format Judul Grup Tanggal agar Cantik (02 Jun 2025)
                ->getTitleFromRecordUsing(function($record) use ($dotPath, $isDate) {
                    $val = Arr::get($record->data, $dotPath);
                    if (!$val) return '-';
                    // Jika tipe kolom date, format cantik. Jika bukan, tampilkan apa adanya.
                    return $isDate ? Carbon::parse($val)->format('d M Y') : (string) $val;
                })

                ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderByRaw("JSON_UNQUOTE($jsonPath) $direction"))
                ->collapsible();
        }

        $tableColumns = [];
        foreach ($dataColumns as $column) {
            $path = []; $tempCol = $column;
            while ($tempCol != null) { array_unshift($path, $tempCol->name); $tempCol = $tempCol->parent; }
            $key = 'data.' . implode('.', $path);
            $dotPath = implode('.', $path);
            
            $quotedPath = collect($path)->map(fn($segment) => '"' . $segment . '"')->join('.');
            $jsonPath = "data->'$." . $quotedPath . "'"; 

            $tableColumn = null;
            switch ($column->type) {
                case 'file':
                    $tableColumn = TextColumn::make($key)
                        ->label($column->name)
                        ->formatStateUsing(fn ($state) => $state ? "Lihat File" : "-")
                        ->icon('heroicon-o-document')
                        // UPDATE KEAMANAN: Pakai disk public secara eksplisit
                        ->url(fn ($state) => $state ? Storage::disk('public')->url($state) : null)
                        ->openUrlInNewTab()
                        ->color('primary');
                    break;
                
                case 'money':
                case 'number':
                    $tableColumn = TextColumn::make($key)->label($column->name);
                    if ($column->type === 'money') {
                        $tableColumn->money('IDR', true);
                    } else {
                        $tableColumn->numeric();
                    }
                    
                    $tableColumn
                        ->alignEnd()
                        ->color(fn (string $state) => match (true) {
                            RecapHelper::cleanNumber($state) < 0 => 'danger',
                            RecapHelper::cleanNumber($state) > 0 => 'success',
                            default => 'gray',
                        });

                    $tableColumn->sortable(query: function (Builder $query, string $direction) use ($jsonPath) {
                        $query->orderByRaw("CAST(JSON_UNQUOTE($jsonPath) AS DECIMAL(15, 2)) $direction");
                    });

                    if ($column->is_summarized) {
                        $tableColumn->summarize(
                            Sum::make()
                                ->money($column->type === 'money' ? 'IDR' : null, true)
                                ->label(stripos($column->name, 'Total') === 0 ? $column->name : 'Total ' . $column->name)
                                ->using(function ($query) use ($dotPath) {
                                    $dataCollection = $query->pluck('data'); 
                                    return $dataCollection->sum(function ($jsonData) use ($dotPath) {
                                        if (is_string($jsonData)) $jsonData = json_decode($jsonData, true);
                                        return RecapHelper::getNumericValue($jsonData ?? [], $dotPath);
                                    });
                                })
                        );
                    }
                    break;
                
                case 'date':
                    $tableColumn = TextColumn::make($key)
                        ->label($column->name)
                        ->date('d M Y')
                        ->sortable(query: function (Builder $query, string $direction) use ($jsonPath) {
                            $query->orderByRaw("CAST(JSON_UNQUOTE($jsonPath) AS DATE) $direction");
                        });
                    break;

                case 'select':
                    $tableColumn = TextColumn::make($key)->label($column->name);
                    if ($column->is_summarized) {
                        $tableColumn->summarize(
                            Sum::make()->label('Total ' . $column->name)
                                ->using(fn ($query) => $query->sum(DB::raw("CAST(JSON_UNQUOTE($jsonPath) AS DECIMAL(15, 2))")))
                        );
                    }
                    $tableColumn->sortable(query: function (Builder $query, string $direction) use ($jsonPath) {
                        $query->orderByRaw("LOWER(JSON_UNQUOTE($jsonPath)) $direction");
                    });
                    break;

                default:
                    $tableColumn = TextColumn::make($key)
                        ->label($column->name)
                        ->wrap()
                        ->sortable(query: function (Builder $query, string $direction) use ($jsonPath) {
                            $query->orderByRaw("LOWER(JSON_UNQUOTE($jsonPath)) $direction");
                        });
                    break;
            }
            
            if($tableColumn) {
                $tableColumn->toggleable();
                if (in_array($column->type, ['text', 'select'])) {
                    $tableColumn->searchable(query: fn ($query, string $search) => $query->whereRaw("LOWER(JSON_UNQUOTE($jsonPath)) LIKE ?", ["%".strtolower($search)."%"]));
                }
                $tableColumns[] = $tableColumn;
            }
        }

        $filters = [];
        foreach ($dataColumns as $column) {
            $path = []; $tempCol = $column;
            while ($tempCol != null) { array_unshift($path, $tempCol->name); $tempCol = $tempCol->parent; }
            $quotedPath = collect($path)->map(fn($s) => '"' . $s . '"')->join('.');
            $jsonPath = "data->'$." . $quotedPath . "'";

            if ($column->type === 'select') {
                $options = $column->options ? array_map('trim', explode(',', $column->options)) : [];
                $filters[] = Tables\Filters\SelectFilter::make('filter_' . $column->id)
                    ->label($column->name)
                    ->options(array_combine($options, $options))
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
            ->groupingSettingsInDropdownOnDesktop()
            ->columns($tableColumns)
            ->filters($filters)
            ->filtersFormColumns(1)
            ->paginated([10, 25, 50, 100]) 
            ->defaultPaginationPageOption(25)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Submit Data')
                    ->modalHeading('Submit Rekapitulasi') 
                    ->createAnother(true),

                ActionGroup::make([
                    Action::make('recalculate')
                        ->label('Hitung Ulang Rumus')
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function () use ($recap, $recapType) {
                            set_time_limit(0); 
                            $allColumns = $recapType->recapColumns()->where('type', '!=', 'group')->get();
                            $formulaColumns = []; $columnMap = [];
                            foreach ($allColumns as $col) {
                                $pathArr = []; $tempCol = $col;
                                while ($tempCol != null) { array_unshift($pathArr, $tempCol->name); $tempCol = $tempCol->parent; }
                                $columnMap[strtolower(trim($col->name))] = ['path' => implode('.', $pathArr), 'type' => $col->type];
                                if ($col->operand_a && $col->operator && $col->operand_b) { $formulaColumns[] = $col; }
                            }
                            if (empty($formulaColumns)) { Notification::make()->title('Tidak ada rumus.')->warning()->send(); return; }

                            $updatedCount = 0;
                            DB::transaction(function() use ($recap, $formulaColumns, $columnMap, &$updatedCount) {
                                $recap->recapRows()->chunkById(200, function ($rows) use ($formulaColumns, $columnMap, &$updatedCount) {
                                    foreach ($rows as $row) {
                                        $data = $row->data;
                                        if (is_string($data)) $data = json_decode($data, true) ?? [];
                                        $isChanged = false;
                                        foreach ($formulaColumns as $fCol) {
                                            $targetPath = $columnMap[strtolower(trim($fCol->name))]['path'] ?? null;
                                            $pathA = $columnMap[strtolower(trim($fCol->operand_a))]['path'] ?? null;
                                            $pathB = $columnMap[strtolower(trim($fCol->operand_b))]['path'] ?? null;
                                            if ($targetPath && $pathA && $pathB) {
                                                $numA = RecapHelper::getNumericValue($data, $pathA);
                                                $numB = RecapHelper::getNumericValue($data, $pathB);
                                                $result = match ($fCol->operator) { '+' => $numA + $numB, '-' => $numA - $numB, '*' => $numA * $numB, '/' => ($numB != 0) ? $numA / $numB : 0, default => 0 };
                                                Arr::set($data, $targetPath, $result);
                                                $isChanged = true;
                                            }
                                        }
                                        if ($isChanged) { $row->update(['data' => $data]); $updatedCount++; }
                                    }
                                });
                            });
                            Notification::make()->title("Sukses! {$updatedCount} data dihitung ulang.")->success()->send();
                        }),

                    Action::make('export_csv')
                        ->label('Export Data CSV')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function () use ($recap, $recapType) {
                            set_time_limit(0); 
                            $filename = 'Export-' . Str::slug($recap->name) . '-' . date('Y-m-d-His') . '.csv';
                            $columns = $recapType->recapColumns()->where('type', '!=', 'group')->orderBy('order')->get();
                            $headers = $columns->pluck('name')->toArray();
                            $columnMap = [];
                            foreach ($columns as $col) {
                                $pathArr = []; $tempCol = $col;
                                while ($tempCol != null) { array_unshift($pathArr, $tempCol->name); $tempCol = $tempCol->parent; }
                                $columnMap[] = implode('.', $pathArr);
                            }
                            return response()->streamDownload(function () use ($recap, $headers, $columnMap) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, $headers);
                                $recap->recapRows()->chunk(1000, function($rows) use ($file, $columnMap) {
                                    foreach ($rows as $row) {
                                        $csvRow = [];
                                        foreach ($columnMap as $path) {
                                            $csvRow[] = Arr::get($row->data, $path);
                                        }
                                        fputcsv($file, $csvRow);
                                    }
                                });
                                fclose($file);
                            }, $filename);
                        }),

                    Action::make('download_template')
                        ->label('Download Template')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function () use ($recap, $dataColumns) {
                            $filename = 'Template-' . Str::slug($recap->name) . '.csv';
                            $headers = $dataColumns->pluck('name')->toArray();
                            $exampleRow = $dataColumns->map(fn ($col) => match ($col->type) {
                                'date' => date('Y-m-d'),
                                'money', 'number' => '1000',
                                'select' => $col->options ? trim(explode(',', $col->options)[0]) : 'Contoh Data',
                                default => 'Contoh ' . $col->name,
                            })->toArray();
                            return response()->streamDownload(fn () => fputcsv($file = fopen('php://output', 'w'), $headers) && fputcsv($file, $exampleRow) && fclose($file), $filename);
                        }),
                    
                    Action::make('import_csv')
                        ->label('Upload CSV')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('primary')
                        ->modalWidth('lg') 
                        ->form([
                            Section::make('Panduan Pengisian & Template')
                                ->description('Silakan unduh template dan baca panduan sebelum upload.')
                                ->icon('heroicon-o-information-circle')
                                ->collapsible()
                                ->schema([
                                    Placeholder::make('instructions')
                                        ->hiddenLabel()
                                        ->content(new HtmlString('
                                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                                <ul class="list-disc list-inside space-y-1 mb-3 text-xs">
                                                    <li><strong>Tanggal:</strong> Gunakan format <code>YYYY-MM-DD</code> (Contoh: 2025-12-31)</li>
                                                    <li><strong>Angka/Uang:</strong> Tulis angka saja, tanpa "Rp" atau titik ribuan (Contoh: 1500000)</li>
                                                    <li><strong>Pemisah (Delimiter):</strong> Gunakan Koma (,) atau Titik Koma (;)</li>
                                                </ul>
                                            </div>
                                        ')),
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('download_template_inner')
                                            ->label('Download Template CSV (+Contoh)')
                                            ->icon('heroicon-o-arrow-down-tray')
                                            ->color('info')
                                            ->size('sm')
                                            ->action(function () use ($recap, $dataColumns) {
                                                $filename = 'Template-' . Str::slug($recap->name) . '.csv';
                                                $headers = $dataColumns->pluck('name')->toArray();
                                                $exampleRow = $dataColumns->map(function ($col) {
                                                    return match ($col->type) {
                                                        'date' => date('Y-m-d'),
                                                        'money', 'number' => '1000',
                                                        'select' => $col->options ? trim(explode(',', $col->options)[0]) : 'Contoh Data',
                                                        default => 'Contoh ' . $col->name,
                                                    };
                                                })->toArray();
                                                return response()->streamDownload(function () use ($headers, $exampleRow) {
                                                    $file = fopen('php://output', 'w');
                                                    fputcsv($file, $headers); 
                                                    fputcsv($file, $exampleRow); 
                                                    fclose($file);
                                                }, $filename);
                                            })
                                    ]),
                                ]),
                            FileUpload::make('file')
                                ->label('File CSV')
                                ->required()
                                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel']),
                            Toggle::make('replace_existing')
                                ->label('Hapus data yang sudah ada?')
                                ->helperText('Hati-hati: Data lama akan dihapus permanen.')
                                ->default(false)
                                ->required(),
                        ])
                        ->action(function (array $data) use ($recap, $recapType) {
                            set_time_limit(0); 
                            $path = Storage::disk('public')->path($data['file']);
                            $handle = fopen($path, 'r');
                            $firstLine = fgets($handle); rewind($handle);
                            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
                            $headers = fgetcsv($handle, 1000, $delimiter);
                            if (!$headers) { Notification::make()->title('CSV Kosong')->danger()->send(); return; }

                            $normalize = fn($str) => strtolower(str_replace(['_', '  '], [' ', ' '], trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $str))));
                            $headers = array_map($normalize, $headers);

                            $allColumns = $recapType->recapColumns()->where('type', '!=', 'group')->get();
                            $columnMap = []; $formulaColumns = [];
                            foreach ($allColumns as $col) {
                                $pathArr = []; $tempCol = $col;
                                while ($tempCol != null) { array_unshift($pathArr, $tempCol->name); $tempCol = $tempCol->parent; }
                                $normalizedColName = $normalize($col->name);
                                $columnMap[$normalizedColName] = [ 'path' => implode('.', $pathArr), 'type' => $col->type ];
                                if ($col->operand_a && $col->operator && $col->operand_b) { $formulaColumns[] = $col; }
                            }

                            $importedCount = 0;
                            try {
                                DB::transaction(function() use ($handle, $delimiter, $headers, $columnMap, $formulaColumns, $normalize, $recap, $data, &$importedCount) {
                                    if (!empty($data['replace_existing']) && $data['replace_existing'] === true) { $recap->recapRows()->delete(); }
                                    while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                                        $rowDataJSON = []; $hasData = false;
                                        foreach ($row as $index => $value) {
                                            $rawHeader = $headers[$index] ?? '';
                                            if (isset($columnMap[$rawHeader])) {
                                                $mapping = $columnMap[$rawHeader];
                                                $cleanVal = trim($value);
                                                if (in_array($mapping['type'], ['money', 'number'])) { $cleanVal = RecapHelper::cleanNumber($value); }
                                                if ($mapping['type'] == 'date' && strtotime($cleanVal)) { $cleanVal = date('Y-m-d', strtotime($cleanVal)); }
                                                if ($cleanVal !== '' && $cleanVal !== null) { Arr::set($rowDataJSON, $mapping['path'], $cleanVal); $hasData = true; }
                                            }
                                        }
                                        if ($hasData && !empty($formulaColumns)) {
                                            foreach ($formulaColumns as $fCol) {
                                                $tPath = $columnMap[$normalize($fCol->name)]['path'] ?? null;
                                                $pA = $columnMap[$normalize($fCol->operand_a)]['path'] ?? null;
                                                $pB = $columnMap[$normalize($fCol->operand_b)]['path'] ?? null;
                                                if ($tPath && $pA && $pB) {
                                                    $valA = RecapHelper::getNumericValue($rowDataJSON, $pA);
                                                    $valB = RecapHelper::getNumericValue($rowDataJSON, $pB);
                                                    $res = match ($fCol->operator) { '+' => $valA + $valB, '-' => $valA - $valB, '*' => $valA * $valB, '/' => ($valB != 0) ? $valA / $valB : 0, default => 0 };
                                                    Arr::set($rowDataJSON, $tPath, $res);
                                                }
                                            }
                                        }
                                        if ($hasData) { $recap->recapRows()->create(['data' => $rowDataJSON]); $importedCount++; }
                                    }
                                });
                                fclose($handle); Storage::disk('public')->delete($data['file']);
                                Notification::make()->title("Sukses! {$importedCount} data diimport.")->success()->send();
                            } catch (\Exception $e) {
                                fclose($handle); Notification::make()->title("Gagal Import: " . $e->getMessage())->danger()->send();
                            }
                        }),

                    Action::make('export_pdf')
                        ->label('Print PDF') 
                        ->icon('heroicon-o-printer') 
                        ->url(fn ($livewire) => route('recap.print', ['record' => $livewire->getOwnerRecord()]))
                        ->openUrlInNewTab(),
                ])
                ->label('Aksi Lainnya')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')         
                ->iconButton()
            ])
            ->toggleColumnsTriggerAction(fn ($action) => $action->iconButton()->icon('heroicon-o-view-columns'))
            ->filtersTriggerAction(fn ($action) => $action->iconButton()->icon('heroicon-o-funnel'))
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }
}