<?php

return [
    'clinic' => [
        'name' => env('CLINIC_NAME', 'PAWLUNE CatCare'),
        'tagline' => 'Klinik Hewan • Boarding • Kasir Terpadu',
        'address' => env('CLINIC_ADDRESS', 'Jl. Melati No. 12, Klaten, Jawa Tengah'),
        'phone' => env('CLINIC_PHONE', '0812-0000-1234'),
        'hours' => 'Senin–Sabtu 08.00–20.00 • Boarding & IGD 24 Jam',
    ],

    // Jam shift (diatur admin pada menu Shift)
    'shifts' => [
        'pagi' => ['label' => 'Pagi', 'time' => '07:00 – 14:00', 'color' => '#FEF08A'],
        'siang' => ['label' => 'Siang', 'time' => '14:00 – 21:00', 'color' => '#A2D2FF'],
        'malam' => ['label' => 'Malam', 'time' => '21:00 – 07:00', 'color' => '#CDB4DB'],
    ],

    'roles' => [
        'admin' => 'Admin',
        'doctor' => 'Dokter',
        'staff' => 'Staff Boarding',
        'cashier' => 'Kasir',
        'groomer' => 'Groomer',
        'owner' => 'Pemilik Hewan',
    ],

    'species' => ['kucing' => 'Kucing', 'anjing' => 'Anjing', 'kelinci' => 'Kelinci', 'burung' => 'Burung', 'hamster' => 'Hamster', 'lainnya' => 'Lainnya'],

    'medicine_categories' => ['Antibiotik', 'Vitamin', 'Vaksin', 'Salep/Luar', 'Obat Cacing', 'Anti Jamur', 'Antiparasit', 'Pereda Nyeri', 'Cairan/Infus', 'Suplemen', 'Lainnya'],

    'tariff_categories' => [
        'pemeriksaan' => 'Pemeriksaan',
        'tindakan' => 'Tindakan Medis',
        'grooming' => 'Grooming / Spa',
        'boarding' => 'Boarding (per hari)',
        'lainnya' => 'Lainnya',
    ],

    // Info pembayaran (tampil di kasir & struk). Ubah lewat .env
    'payment' => [
        'qris_merchant' => env('QRIS_MERCHANT', 'PAWLUNE CATCARE'),
        // Jika punya gambar QRIS statis asli, taruh di public/img/qris.png -> otomatis dipakai.
        'banks' => [
            ['bank' => env('BANK1_NAME', 'BCA'), 'number' => env('BANK1_NUMBER', '1234567890'), 'holder' => env('BANK1_HOLDER', 'PT Pawlune CatCare')],
            ['bank' => env('BANK2_NAME', 'BRI'), 'number' => env('BANK2_NUMBER', '0987654321'), 'holder' => env('BANK2_HOLDER', 'PT Pawlune CatCare')],
        ],
    ],
];
