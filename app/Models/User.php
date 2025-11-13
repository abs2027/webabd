<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser; // <-- TAMBAHKAN INI
use Filament\Models\Contracts\HasTenants;    // <-- TAMBAHKAN INI
use Filament\Panel;                         // <-- TAMBAHKAN INI
use Illuminate\Database\Eloquent\Collection;  // <-- TAMBAHKAN INI
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;       // <-- TAMBAHKAN INI
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
        ];
    }

    /**
     * Relasi User ke Company.
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    // =================================================================
    // METODE-METODE BARU UNTUK FILAMENT MULTITENANCY
    // =================================================================

    /**
     * Metode ini adalah jawaban untuk error Anda.
     * Kita beritahu Filament di mana menemukan daftar tenant (perusahaan)
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->companies()->get(); // <-- Tambahkan ->get()
    }

    /**
     * Metode ini untuk mengecek apakah user boleh mengakses tenant (perusahaan)
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->companies()->where('company_id', $tenant->id)->exists();
    }

    /**
     * Metode ini adalah standar agar user bisa mengakses panel Filament
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Asumsikan semua user bisa mengakses panel
    }
}