<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    // Mengubah judul halaman biar terasa seperti Dashboard
    public function getTitle(): string
    {
        return 'Kelola Proyek'; 
    }

    protected function getHeaderActions(): array
    {
        return [
            // Kita taruh tombol "Ubah Info" di pojok atas juga
            Actions\EditAction::make()
                ->label('Ubah Info Kontrak')
                ->color('gray'),
        ];
    }
}