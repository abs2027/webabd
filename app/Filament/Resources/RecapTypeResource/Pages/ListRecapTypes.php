<?php

namespace App\Filament\Resources\RecapTypeResource\Pages;

use App\Filament\Resources\RecapTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecapTypes extends ListRecords
{
    protected static string $resource = RecapTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
