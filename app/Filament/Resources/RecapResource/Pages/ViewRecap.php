<?php

namespace App\Filament\Resources\RecapResource\Pages;

use App\Filament\Resources\RecapResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRecap extends ViewRecord
{
    protected static string $resource = RecapResource::class;

    public function getTitle(): string
    {
        return 'Input Data Rekapitulasi';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Ubah Info Periode')
                ->color('gray'),
        ];
    }
}