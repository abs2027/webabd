<?php

namespace App\Filament\Resources\RecapTypeResource\Pages;

use App\Filament\Resources\RecapTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRecapType extends ViewRecord
{
    protected static string $resource = RecapTypeResource::class;

    // Judul Dashboard
    public function getTitle(): string
    {
        return 'Detail Jenis Rekapitulasi'; 
    }

    protected function getHeaderActions(): array
    {
        return [
            // Tombol ubah info (Nama/Deskripsi) kecil di pojok
            Actions\EditAction::make()
                ->label('Ubah Info')
                ->color('gray'),
        ];
    }
}