<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatCareSeeder::class);
        // Data contoh transaksi 10 hari (untuk lihat grafik). Hapus baris ini jika tidak mau data contoh:
        $this->call(DemoDataSeeder::class);
    }
}
