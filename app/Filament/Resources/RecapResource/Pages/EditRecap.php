<?php

namespace App\Filament\Resources\RecapResource\Pages;

use App\Filament\Resources\RecapResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProjectResource; // <-- Import
use Filament\Notifications\Notification; // <-- Import

class EditRecap extends EditRecord
{
    protected static string $resource = RecapResource::class;

    protected function getHeaderActions(): array
    {
        // Tombol Hapus Kustom kita (ini sudah benar)
        return [
            Actions\Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(function () {
                    $record = $this->record;
                    $projectId = $record->project_id;
                    $record->delete();
                    Notification::make()
                        ->title('Data periode berhasil dihapus')
                        ->success()
                        ->send();
                    $this->redirect(ProjectResource::getUrl('edit', ['record' => $projectId]));
                }),
        ];
    }

    // ▼▼▼ INI PERBAIKAN BARUNYA ▼▼▼
    // Fungsi ini akan mengganti breadcrumb 'Recap > Ubah'
    public function getBreadcrumbs(): array
    {
        // Ambil data Recap ('Periode November')
        $record = $this->record;
        // Ambil data Project induknya
        $project = $record->project;

        return [
            // Link ke halaman daftar Proyek
            ProjectResource::getUrl('index') => 'Proyek',
            
            // Link ke halaman 'edit' Proyek induk
            ProjectResource::getUrl('edit', ['record' => $project->id]) => $project->name,
            
            // Teks untuk halaman ini (tidak bisa diklik)
            'Input Data Rekapitulasi',
        ];
    }
    // ▲▲▲ SELESAI ▲▲▲
}