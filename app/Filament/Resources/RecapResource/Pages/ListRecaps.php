<?php

namespace App\Filament\Resources\RecapResource\Pages;

use App\Filament\Resources\RecapResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecaps extends ListRecords
{
    protected static string $resource = RecapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
