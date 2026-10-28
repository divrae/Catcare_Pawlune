<div align="center">

# 🐾 PAWLUNE CatCare

**Sistem manajemen klinik hewan berbasis Laravel**
antrean online • pemeriksaan & resep • boarding • grooming • kasir • dashboard admin

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Laragon-4479A1?logo=mysql&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-4.4-FF6384?logo=chartdotjs&logoColor=white)
![Tests](https://img.shields.io/badge/tests-12%20passed-brightgreen)

![Beranda](docs/screenshots/01-beranda.png)

</div>

## Tentang proyek

Pawlune CatCare menyatukan seluruh alur klinik hewan dalam satu website: pemilik mengambil antrean dari rumah, dokter memeriksa dan meresepkan, hewan bisa dititipkan di boarding atau digrooming, lalu kasir menghitung semuanya otomatis dalam satu tagihan.

📺 **Video penjelasan:** [Tonton di YouTube](https://youtu.be/8ho8ejUCSXQ?si=Y0ueZgwJiN_bVCVD)

## Fitur

- **Pemilik hewan:** daftar dengan nomor HP, Soulmate Pass (kartu digital + QR), ambil antrean, notifikasi saat dipanggil
- **Dokter:** panggil antrean, periksa, tindakan, resep terhubung stok obat, rujuk ke boarding
- **Boarding:** peta 10 suite, catatan harian (makan, suhu, kesehatan)
- **Grooming:** antrean sendiri, tamu boarding otomatis masuk tagihan boarding
- **Kasir:** satu tagihan gabungan, bayar tunai / QRIS / transfer, struk
- **Admin:** dashboard grafik, staf & shift, tarif, obat & stok, rekap cetak dan PDF

## Cara pasang (Laragon)

1. Letakkan project di `C:\laragon\www\catcare`, lalu **Start All** di Laragon.
2. Di Terminal Laragon, jalankan:
   ```bash
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
3. Buat database kosong `catcare_db` di HeidiSQL, lalu:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```
4. Buka `http://catcare.test` atau jalankan `php artisan serve`.

Tabel dibuat dari migration, jadi tidak perlu impor file `.sql`.

## Akun demo

Password semua akun: `password`

| Peran | Login |
|---|---|
| Admin | `admin@catcare.com` |
| Dokter | `doctor@catcare.com` |
| Staf boarding | `staff@catcare.com` |
| Groomer | `groomer@catcare.com` |
| Kasir | `cashier@catcare.com` |
| Pemilik hewan | `08129981229` |

## Tes

```bash
php artisan test
```

## Pengembang

Dibuat oleh **Diva Paramida**, XII PPLG 1, 2026.
