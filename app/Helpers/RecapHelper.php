<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RecapHelper
{
    /**
     * Membersihkan string format uang/angka menjadi float murni.
     * Support format:
     * - Indonesia: 1.500.000,00
     * - US: 1,500,000.00
     * - Akuntansi: (500.000) -> menjadi minus -500000
     */
    public static function cleanNumber(mixed $val): float
    {
        $valStr = trim((string) $val);
        
        // Deteksi Format Akuntansi Tanda Kurung: (150.000)
        $isNegativeWrapper = false;
        if (str_starts_with($valStr, '(') && str_ends_with($valStr, ')')) {
            $isNegativeWrapper = true;
        }

        // Hapus semua karakter kecuali angka, koma, titik, dan minus
        $valStr = preg_replace('/[^\d,.-]/', '', $valStr); 
        
        if ($valStr === '') return 0;
        
        // Deteksi Ribuan Titik (Format Indo: 1.500.000)
        if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $valStr)) { 
            $valStr = str_replace('.', '', $valStr); 
        }
        // Deteksi Ribuan Koma (Format US: 1,500,000)
        elseif (preg_match('/^-?\d{1,3}(,\d{3})+$/', $valStr)) { 
            $valStr = str_replace(',', '', $valStr); 
        }
        // Deteksi Decimal Campuran
        else {
            $lastDot = strrpos($valStr, '.'); 
            $lastComma = strrpos($valStr, ',');
            
            // Jika Koma muncul SETELAH Titik (10.000,50) -> Indo
            if ($lastComma !== false && ($lastDot === false || $lastComma > $lastDot)) {
                $valStr = str_replace('.', '', $valStr); // Hapus ribuan
                $valStr = str_replace(',', '.', $valStr); // Ubah desimal jadi titik
            } else {
                // Format US/Default -> Hapus koma ribuan
                $valStr = str_replace(',', '', $valStr);
            }
        }
        
        $number = (float) $valStr;

        // Jika tadi pakai tanda kurung, kalikan -1
        if ($isNegativeWrapper) {
            $number = abs($number) * -1;
        }

        return $number;
    }

    /**
     * Mengambil nilai dari JSON array berdasarkan path (dot notation)
     * dan membersihkannya menjadi angka.
     */
    public static function getNumericValue(array $data, string $dotPath): float
    {
        // Coba ambil langsung
        $val = Arr::get($data, $dotPath);

        // Jika tidak ketemu, coba cari case-insensitive (fallback)
        if ($val === null) {
            $flattened = Arr::dot($data);
            foreach ($flattened as $key => $v) {
                if (Str::endsWith(strtolower($key), strtolower($dotPath))) {
                    $val = $v;
                    break;
                }
            }
        }

        return self::cleanNumber($val);
    }
}