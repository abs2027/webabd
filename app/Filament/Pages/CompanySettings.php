<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
// =======================================================
// TAMBAHAN BARU UNTUK FORM
// =======================================================
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use App\Models\Company;
use Filament\Notifications\Notification;
// =======================================================

class CompanySettings extends Page implements HasForms // <-- TAMBAHKAN IMPLEMENTS
{
    use InteractsWithForms; // <-- TAMBAHKAN USE TRAIT

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Profil Perusahaan';
    protected static ?string $title = 'Profil Perusahaan';

    // Ini sudah benar, menunjuk ke file Blade yang akan kita buat
    protected static string $view = 'filament.pages.company-settings';

    // Properti untuk menampung data form
    public ?array $data = [];

    /**
     * Saat halaman dimuat, isi form dengan data tenant
     */
    public function mount(): void
    {
        $company = Filament::getTenant();
        $this->form->fill($company->toArray());
    }

    /**
     * Definisikan Form Anda (Logo, Alamat, dll)
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kop Surat')
                    ->description('Data ini akan digunakan untuk kop surat.')
                    ->schema([
                        TextInput::make('business_description')
                            ->label('Deskripsi Bisnis (Tagline)')
                            ->placeholder('General Contractor, Civil, Supplier & Trade')
                            ->columnSpanFull(),
                        FileUpload::make('logo_path')
                            ->label('Logo Perusahaan')
                            ->image()
                            ->directory('company-logos') // Pastikan storage:link
                            ->columnSpanFull(),

                        Textarea::make('address')
                            ->label('Alamat Perusahaan')
                            ->rows(3)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel(),

                        TextInput::make('email')
                            ->label('Email Perusahaan')
                            ->email(),
                    ])->columns(2),
            ])
            ->model(Filament::getTenant()) // Bind ke model tenant
            ->statePath('data'); // Kirim data ke properti $data
    }

    /**
     * Simpan data ke Tenant (Company) saat ini
     */
    public function save(): void
    {
        try {
            $company = Filament::getTenant();
            $data = $this->form->getState();
            
            $company->update($data);

            Notification::make()
                ->title('Data berhasil disimpan')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan data')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}