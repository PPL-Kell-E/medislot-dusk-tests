# MediSlot — Dusk Browser Tests

Repository ini menampung file-file **Laravel Dusk** untuk pengujian end-to-end fitur MediSlot.

## Struktur

```
medislot-dusk-tests/
├── tests/
│   ├── DuskTestCase.php                ← Base class untuk semua Dusk tests
│   └── Browser/
│       ├── PKE1_ProfilTest.php         ← 12 TC: Pengelolaan Profil
│       ├── PKE2_DataKesehatanTest.php  ← 21 TC: Data Kesehatan Dasar
│       ├── PKE5_JadwalTest.php         ← 25 TC: Perencanaan Jadwal Pemeriksaan
│       ├── PKE6_AuthTest.php           ← 12 TC: Registrasi, Login, Logout
│       ├── PKE8_PengelolaanJadwalTest.php ← 14 TC: Edit & Hapus Jadwal
│       ├── Pages/
│       │   ├── Page.php                ← Base Page Object
│       │   ├── ProfilePage.php         ← Page Object untuk /profile
│       │   └── DataKesehatanPage.php   ← Page Object untuk /data-kesehatan
│       ├── screenshots/                ← Screenshot otomatis saat test gagal
│       └── console/                    ← Console log saat test gagal
├── .env.dusk.local                     ← Env override saat menjalankan Dusk
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

## Test Cases (PKE-5 Perencanaan Jadwal Pemeriksaan)

| No | Test Case | Deskripsi |
|----|-----------|-----------|
| TC-01 | `test_tc01_membuka_form_tambah_jadwal` | Klik "Tambah Jadwal" → navigasi ke /jadwal/create |
| TC-02 | `test_tc02_menampilkan_form_penjadwalan` | Form menampilkan field jenis, fasilitas, tanggal, waktu |
| TC-03 | `test_tc03_memilih_jenis_pemeriksaan` | Input jenis pemeriksaan diterima dan tersimpan di form |
| TC-04 | `test_tc04_memilih_tanggal_pemeriksaan` | Input tanggal diterima dan tersimpan di form |
| TC-05 | `test_tc05_menampilkan_slot_tanggal_tersedia` | Tanggal mendatang → info "Tanggal tersedia" muncul |
| TC-06 | `test_tc06_memilih_waktu_pemeriksaan` | Input waktu diterima dan tersimpan di form |
| TC-07 | `test_tc07_menampilkan_slot_waktu_tersedia` | Setelah pilih waktu → info "Waktu tersedia" muncul |
| TC-08 | `test_tc08_validasi_slot_jadwal_tersedia` | Tanggal mendatang → indikator slot tersedia tampil |
| TC-09 | `test_tc09_validasi_slot_tanggal_sudah_lewat` | Tanggal lewat → info "Tanggal sudah lewat" muncul |
| TC-10 | `test_tc10_menyimpan_jadwal_pemeriksaan` | Isi form lengkap → data tersimpan di database |
| TC-11 | `test_tc11_menampilkan_ringkasan_jadwal` | Setelah buat jadwal → card jadwal tampil di index |
| TC-12 | `test_tc12_menampilkan_detail_jadwal` | Card jadwal tampil detail + tombol Edit & Hapus |
| TC-13 | `test_tc13_membuka_form_edit_jadwal` | Klik Edit → form edit terbuka dengan data sebelumnya |
| TC-14 | `test_tc14_mengubah_tanggal_pemeriksaan` | Ubah tanggal di form edit → nilai baru tersimpan |
| TC-15 | `test_tc15_mengubah_waktu_pemeriksaan` | Ubah waktu di form edit → nilai baru tersimpan |
| TC-16 | `test_tc16_validasi_slot_saat_edit` | Set tanggal + waktu baru di edit → field menerima nilai |
| TC-17 | `test_tc17_memperbarui_jadwal` | Simpan edit → database diperbarui |
| TC-18 | `test_tc18_notifikasi_update_berhasil` | Simpan edit → notifikasi "Jadwal berhasil diperbarui" |
| TC-19 | `test_tc19_tombol_hapus_jadwal_tersedia` | Tombol Hapus tersedia pada card jadwal |
| TC-20 | `test_tc20_dialog_konfirmasi_hapus` | Klik Hapus → native confirm() dialog muncul |
| TC-21 | `test_tc21_konfirmasi_hapus_jadwal` | Konfirmasi hapus → data terhapus dari database |
| TC-22 | `test_tc22_notifikasi_hapus_berhasil` | Hapus berhasil → notifikasi "Jadwal berhasil dihapus" |
| TC-23 | `test_tc23_membatalkan_hapus_jadwal` | Batal hapus → data tetap ada di database |
| TC-24 | `test_tc24_edit_jadwal_status_selesai` | Edit jadwal status selesai → form edit dapat diakses |
| TC-25 | `test_tc25_menampilkan_data_jadwal_terbaru` | Setelah update → list jadwal menampilkan data terbaru |

## Cara Menjalankan Per PKE

```bash
# PKE-1 Pengelolaan Profil
php artisan dusk tests/Browser/PKE1_ProfilTest.php

# PKE-2 Data Kesehatan Dasar
php artisan dusk tests/Browser/PKE2_DataKesehatanTest.php

# PKE-5 Perencanaan Jadwal Pemeriksaan
php artisan dusk tests/Browser/PKE5_JadwalTest.php
```

## Hasil Terakhir

| PKE | Tests | Assertions | Duration |
|-----|-------|-----------|----------|
| PKE-1 | 12 passed | 26 | ~48s |
| PKE-2 | 21 passed | 37 | ~86s |
| PKE-5 | 25 passed | 42 | ~81s |
| PKE-6 | 12 passed | 24 | ~60s |
| PKE-8 | 14 passed | 28 | ~76s |

Dijalankan pada: **2026-06-09** | Laravel 13.5 | Chrome 148 | PHP 8.x
