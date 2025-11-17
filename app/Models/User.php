<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser; // <-- PENTING
use Filament\Models\Contracts\HasTenants;    // <-- PENTING
use Filament\Panel;                         // <-- PENTING
use Illuminate\Database\Eloquent\Collection;  // <-- PENTING
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;       // <-- PENTING
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Implementasikan contract FilamentUser dan HasTenants
class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_superadmin', // <-- Pastikan ini ada di $fillable!
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean', // <-- Tambahkan cast ini
        ];
    }

    /**
     * Relasi User ke Company.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    /**
     * Relasi default (mungkin untuk tenant saat ini)
     * Catatan: Ini mungkin tidak diperlukan jika Anda hanya menggunakan relasi many-to-many.
     * Hapus jika tidak ada kolom company_id di tabel users.
     */
    // public function company(): BelongsTo
    // {
    //     return $this->belongsTo(Company::class);
    // }

    // =================================================================
    // METODE-METODE BARU UNTUK FILAMENT MULTITENANCY
    // =================================================================

    /**
     * Memberitahu Filament daftar tenant (perusahaan) yang dimiliki user ini.
     * Ini digunakan oleh panel 'admin' (tenant).
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->companies()->get();
    }

    /**
     * Mengecek apakah user boleh mengakses tenant (perusahaan) tertentu.
     * Ini digunakan oleh panel 'admin' (tenant).
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->companies()->where('company_id', $tenant->id)->exists();
    }

    /**
     * Mengecek apakah user boleh mengakses panel tertentu.
     * INI ADALAH KUNCI UNTUK MEMPERBAIKI ERROR 403 ANDA.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Jika ID panelnya adalah 'superadmin'
        if ($panel->getId() === 'superadmin') {
            // Cek kolom 'is_superadmin'
            return $this->is_superadmin === true;
        }

        // Untuk panel 'admin' (panel tenant Anda)
        if ($panel->getId() === 'admin') {
            // Izinkan semua user yang terautentikasi (logika tenant akan menangani sisanya)
            return true; 
        }

        // Default tolak
        return false;
    }
}