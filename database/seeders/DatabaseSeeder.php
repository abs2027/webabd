<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- 1. Buat User SUPER ADMIN ---
        // User ini bisa mengakses panel /superadmin
        $superAdmin = User::create([
            'name' => 'Fajar (Superadmin)',
            'email' => 'fjrdptra@gmail.com',
            'password' => Hash::make('password'),
            'is_superadmin' => true, // <-- INI YANG DITAMBAHKAN
        ]);

        // --- 2. Buat Perusahaan (Tenant) ---
        $company1 = Company::create([
            'name' => 'PT ABD ABADI JAYA',
            'slug' => Str::slug('PT ABD ABADI JAYA')
        ]);
        $company2 = Company::create([
            'name' => 'PT ABD JAYA FAMILY',
            'slug' => Str::slug('PT ABD JAYA FAMILY')
        ]);

        // --- 3. Hubungkan User SUPER ADMIN ke Perusahaan ---
        // Tergantung logika bisnis Anda, superadmin mungkin
        // tidak perlu terhubung ke company, tapi kita hubungkan saja
        // agar datanya konsisten dengan kode Anda sebelumnya.
        $superAdmin->companies()->attach([$company1->id, $company2->id]);


        // --- (REKOMENDASI) 4. Buat User BIASA (Tenant User) ---
        // User ini HANYA bisa mengakses panel /admin (panel tenant)
        // dan TIDAK bisa mengakses /superadmin.
        $normalUser = User::create([
            'name' => 'User Biasa',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_superadmin' => false, // <-- default-nya false, tapi eksplisit lebih baik
        ]);

        // Hubungkan user biasa ini HANYA ke satu perusahaan
        $normalUser->companies()->attach($company1->id);
    }
}