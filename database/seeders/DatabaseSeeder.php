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
        // 1. Buat User Admin Utama
        $user = User::create([
            'name' => 'Fajar',
            'email' => 'fjrdptra@gmail.com',
            'password' => Hash::make('password'),
        ]);

        // 2. Buat Perusahaan (Tenant) - DENGAN SLUG
        $company1 = Company::create([
            'name' => 'PT ABD ABADI JAYA',
            'slug' => Str::slug('PT ABD ABADI JAYA') // <-- 2. TAMBAHKAN SLUG
        ]);
        $company2 = Company::create([
            'name' => 'PT ABD JAYA FAMILY',
            'slug' => Str::slug('PT ABD JAYA FAMILY') // <-- 2. TAMBAHKAN SLUG
        ]);

        // 3. Hubungkan User ke Perusahaan
        $user->companies()->attach([$company1->id, $company2->id]);
    }
}
