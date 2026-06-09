# MediSlot — Dusk Browser Tests

Repository ini menampung file-file **Laravel Dusk** untuk pengujian end-to-end fitur MediSlot.

## Struktur

```
medislot-dusk-tests/
├── tests/
│   ├── DuskTestCase.php            ← Base class untuk semua Dusk tests
│   └── Browser/
│       ├── PKE1_ProfilTest.php     ← 18 TC: Pengelolaan Profil (PKE-1, PKE-22, PKE-23)
│       ├── Pages/
│       │   ├── Page.php            ← Base Page Object
│       │   └── ProfilePage.php     ← Page Object untuk /profile
│       ├── screenshots/            ← Screenshot otomatis saat test gagal
│       └── console/                ← Console log saat test gagal
├── .env.dusk.local                 ← Env override saat menjalankan Dusk
└── README.md
```

## Cara Menjalankan

### Prasyarat
1. Project **MediSlot** sudah di-clone dan terinstall di local
2. Server berjalan: `php artisan serve` (port 8000)
3. ChromeDriver sudah terinstall (otomatis via `php artisan dusk:chrome-driver`)

### Langkah

```bash
# 1. Copy file-file ini ke dalam project MediSlot (folder yang sama)
cp -r tests/Browser/PKE1_ProfilTest.php   <medislot>/tests/Browser/
cp -r tests/Browser/Pages/ProfilePage.php  <medislot>/tests/Browser/Pages/
cp -r tests/DuskTestCase.php               <medislot>/tests/
cp .env.dusk.local                         <medislot>/

# 2. Install Dusk (jika belum)
cd <medislot>
composer require laravel/dusk --dev
php artisan dusk:install

# 3. Jalankan server di terminal lain
php artisan serve

# 4. Jalankan semua Dusk test PKE-1
php artisan dusk tests/Browser/PKE1_ProfilTest.php

# 5. Jalankan satu test case spesifik
php artisan dusk tests/Browser/PKE1_ProfilTest.php --filter=tc01_akses_halaman_profil
```

## Test Cases (PKE-1 Pengelolaan Profil)

| No  | Test Case | Deskripsi |
|-----|-----------|-----------|
| TC-01 | `test_tc01_akses_halaman_profil` | User login → navigasi ke /profile → halaman tampil |
| TC-02 | `test_tc02_data_profil_tampil` | Nama, usia, jenis kelamin tampil sesuai DB |
| TC-03 | `test_tc03_mode_read_only_saat_dibuka` | Halaman dibuka dalam view mode (form tersembunyi) |
| TC-04 | `test_tc04_profil_tidak_lengkap_tampil_tanpa_error` | Field kosong tampil sebagai '-', tanpa 500 error |
| TC-05 | `test_tc05_klik_ubah_profil_form_muncul` | Klik Edit → form edit muncul |
| TC-06 | `test_tc06_autofill_saat_masuk_edit_mode` | Form edit ter-fill dengan data sebelumnya |
| TC-07 | `test_tc07_input_valid_data_diterima` | Input valid diterima, tidak ada pesan error |
| TC-08 | `test_tc08_nama_kosong_error_mandatory` | Nama kosong → "Nama lengkap wajib diisi." |
| TC-09 | `test_tc09_usia_kosong_error_mandatory` | Usia kosong → "Usia wajib diisi." |
| TC-10 | `test_tc10_usia_non_angka_ditolak` | Input non-angka → field type=number membuang karakter |
| TC-11 | `test_tc11_usia_negatif_ditolak` | Usia = -5 → "Usia harus berupa angka positif." |
| TC-12 | `test_tc12_usia_nol_ditolak` | Usia = 0 → "Usia harus berupa angka positif." |
| TC-13 | `test_tc13_usia_desimal_ditolak` | Usia = 21.5 → error validasi integer dari server |
| TC-14 | `test_tc14_jenis_kelamin_tidak_dipilih_error` | Jenis kelamin kosong → "Jenis kelamin wajib dipilih." |
| TC-15 | `test_tc15_simpan_valid_tersimpan_ke_database` | Simpan valid → data benar tersimpan di DB |
| TC-16 | `test_tc16_tidak_simpan_data_tidak_berubah` | Klik Batal → data DB tidak berubah |
| TC-17 | `test_tc17_notifikasi_sukses_muncul` | Simpan berhasil → flash "Profil berhasil diperbarui" |
| TC-18 | `test_tc18_data_terupdate_setelah_refresh` | Refresh setelah simpan → data terbaru tampil |

## Hasil Terakhir

```
Tests: 18 passed (33 assertions)
Duration: ~61s
```

Dijalankan pada: **2026-06-09** | Laravel 13.5 | Chrome 148 | PHP 8.x
