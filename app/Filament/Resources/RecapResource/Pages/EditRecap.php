<?php

namespace App\Filament\Resources\RecapResource\Pages;

use App\Filament\Resources\RecapResource;
use App\Filament\Resources\RecapTypeResource; 
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecap extends EditRecord
{
    protected static string $resource = RecapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ▼▼▼ PERBAIKAN UTAMA DI SINI ▼▼▼
            DeleteAction::make()
                // Kita wajib set redirect manual karena halaman index sudah tidak ada
                ->successRedirectUrl(function ($record) {
                    // Balik ke halaman Induk (Detail Jenis Rekap) setelah dihapus
                    return RecapTypeResource::getUrl('view', ['record' => $record->recapType]);
                }),
            // ▲▲▲ SELESAI PERBAIKAN ▲▲▲
        ];
    }

    public function getBreadcrumbs(): array
    {
        $record = $this->getRecord();
        $record->load('recapType');

        return [
            RecapTypeResource::getUrl('view', ['record' => $record->recapType]) => $record->recapType->name,
            $this->getResource()::getUrl('view', ['record' => $record]) => $record->name,
            'edit' => 'Ubah Info',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}