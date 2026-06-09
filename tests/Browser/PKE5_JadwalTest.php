<?php

namespace Tests\Browser;

use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PKE5_JadwalTest extends DuskTestCase
{
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $uniqueId = Str::random(8);

        $this->testUser = User::create([
            'full_name'     => 'Test Dusk PKE5',
            'email'         => "dusk.pke5.{$uniqueId}@test.local",
            'password_hash' => Hash::make('Password123!'),
            'role'          => 'user',
        ]);
    }

    protected function tearDown(): void
    {
        Jadwal::where('user_id', $this->testUser->id)->delete();
        $this->testUser->delete();

        parent::tearDown();
    }

    private function loginAndGoToJadwal(Browser $browser): Browser
    {
        return $browser->visit('/login')
                       ->waitFor('input[name="email"]', 5)
                       ->type('input[name="email"]', $this->testUser->email)
                       ->type('input[name="password"]', 'Password123!')
                       ->press('Sign In')
                       ->waitForLocation('/dashboard', 5)
                       ->visit('/jadwal')
                       ->waitFor('.btn-tambah', 5);
    }

    private function createJadwal(array $override = []): Jadwal
    {
        return Jadwal::create(array_merge([
            'user_id'           => $this->testUser->id,
            'jenis_pemeriksaan' => 'Pemeriksaan Umum',
            'fasilitas_klinik'  => 'Klinik Medina',
            'tanggal'           => now()->addDays(7)->format('Y-m-d'),
            'waktu'             => '09:00',
            'catatan'           => null,
            'status'            => 'mendatang',
        ], $override));
    }

    /** @test TC-01: Membuka form tambah jadwal */
    public function test_tc01_membuka_form_tambah_jadwal(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-tambah')
                 ->waitForLocation('/jadwal/create', 5)
                 ->assertPathIs('/jadwal/create')
                 ->screenshot('tc01-membuka-form-tambah-jadwal');
        });
    }

    /** @test TC-02: Menampilkan form penjadwalan (field jenis, tanggal, waktu) */
    public function test_tc02_menampilkan_form_penjadwalan(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('#jadwalForm', 5)
                 ->assertVisible('input[name="jenis_pemeriksaan"]')
                 ->assertVisible('input[name="fasilitas_klinik"]')
                 ->assertVisible('input[name="tanggal"]')
                 ->assertVisible('input[name="waktu"]')
                 ->screenshot('tc02-form-penjadwalan-tampil');
        });
    }

    /** @test TC-03: Memilih jenis pemeriksaan */
    public function test_tc03_memilih_jenis_pemeriksaan(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->type('input[name="jenis_pemeriksaan"]', 'Pemeriksaan Gigi')
                 ->assertInputValue('input[name="jenis_pemeriksaan"]', 'Pemeriksaan Gigi')
                 ->screenshot('tc03-memilih-jenis-pemeriksaan');
        });
    }

    /** @test TC-04: Memilih tanggal pemeriksaan */
    public function test_tc04_memilih_tanggal_pemeriksaan(): void
    {
        $tanggal = now()->addDays(3)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($tanggal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('input[name="tanggal"]', 5);

            $browser->script("document.querySelector('input[name=\"tanggal\"]').value = '{$tanggal}';");

            $actual = $browser->value('input[name="tanggal"]');
            $this->assertEquals($tanggal, $actual);

            $browser->screenshot('tc04-memilih-tanggal-pemeriksaan');
        });
    }

    /** @test TC-05: Menampilkan slot tanggal tersedia */
    public function test_tc05_menampilkan_slot_tanggal_tersedia(): void
    {
        $tanggal = now()->addDays(5)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($tanggal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('#inputTanggal', 5);

            $browser->script("
                var el = document.getElementById('inputTanggal');
                el.value = '{$tanggal}';
                el.dispatchEvent(new Event('change'));
            ");

            $browser->waitFor('#slotInfo.show', 5)
                    ->assertSee('Tanggal tersedia')
                    ->screenshot('tc05-slot-tanggal-tersedia');
        });
    }

    /** @test TC-06: Memilih waktu pemeriksaan */
    public function test_tc06_memilih_waktu_pemeriksaan(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('input[name="waktu"]', 5)
                 ->type('input[name="waktu"]', '09:00')
                 ->assertInputValue('input[name="waktu"]', '09:00')
                 ->screenshot('tc06-memilih-waktu-pemeriksaan');
        });
    }

    /** @test TC-07: Menampilkan slot waktu pemeriksaan tersedia */
    public function test_tc07_menampilkan_slot_waktu_tersedia(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('#inputWaktu', 5);

            $browser->script("
                var el = document.getElementById('inputWaktu');
                el.value = '10:00';
                el.dispatchEvent(new Event('change'));
            ");

            $browser->waitFor('#waktuInfo.show', 5)
                    ->assertSee('Waktu tersedia')
                    ->screenshot('tc07-slot-waktu-tersedia');
        });
    }

    /** @test TC-08: Validasi slot jadwal tersedia (tanggal mendatang) */
    public function test_tc08_validasi_slot_jadwal_tersedia(): void
    {
        $tanggal = now()->addDays(7)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($tanggal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('#inputTanggal', 5);

            $browser->script("
                var el = document.getElementById('inputTanggal');
                el.value = '{$tanggal}';
                el.dispatchEvent(new Event('change'));
            ");

            $browser->waitFor('#slotInfo.show', 5)
                    ->assertVisible('#slotInfo')
                    ->screenshot('tc08-validasi-slot-tersedia');
        });
    }

    /** @test TC-09: Validasi slot jadwal tidak tersedia (tanggal sudah lewat) */
    public function test_tc09_validasi_slot_tanggal_sudah_lewat(): void
    {
        $tanggalLewat = now()->subDays(1)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($tanggalLewat) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('#inputTanggal', 5);

            $browser->script("
                var el = document.getElementById('inputTanggal');
                el.value = '{$tanggalLewat}';
                el.dispatchEvent(new Event('change'));
            ");

            $browser->pause(500)
                    ->assertVisible('#pastInfo')
                    ->assertMissing('#slotInfo.show')
                    ->screenshot('tc09-validasi-slot-tanggal-lewat');
        });
    }

    /** @test TC-10: Menyimpan jadwal pemeriksaan ke database */
    public function test_tc10_menyimpan_jadwal_pemeriksaan(): void
    {
        $tanggal = now()->addDays(10)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($tanggal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit('/jadwal/create')
                 ->waitFor('#jadwalForm', 5)
                 ->type('input[name="jenis_pemeriksaan"]', 'Pemeriksaan Darah')
                 ->type('input[name="fasilitas_klinik"]', 'Klinik Sehat')
                 ->type('input[name="tanggal"]', $tanggal)
                 ->type('input[name="waktu"]', '10:00')
                 ->click('.btn-simpan')
                 ->waitForLocation('/jadwal', 5)
                 ->assertPathIs('/jadwal')
                 ->screenshot('tc10-menyimpan-jadwal');
        });

        $this->assertDatabaseHas('jadwal', [
            'user_id'           => $this->testUser->id,
            'jenis_pemeriksaan' => 'Pemeriksaan Darah',
            'fasilitas_klinik'  => 'Klinik Sehat',
        ]);
    }

    /** @test TC-11: Menampilkan ringkasan jadwal setelah berhasil dibuat */
    public function test_tc11_menampilkan_ringkasan_jadwal(): void
    {
        $this->createJadwal(['jenis_pemeriksaan' => 'Pemeriksaan Mata']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->assertSee('Pemeriksaan Mata')
                 ->assertVisible('.jadwal-card')
                 ->screenshot('tc11-ringkasan-jadwal');
        });
    }

    /** @test TC-12: Menampilkan detail jadwal beserta tombol Edit dan Hapus */
    public function test_tc12_menampilkan_detail_jadwal(): void
    {
        $this->createJadwal([
            'jenis_pemeriksaan' => 'Cek Kolesterol',
            'fasilitas_klinik'  => 'RS Harapan',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->assertSee('Cek Kolesterol')
                 ->assertSee('RS Harapan')
                 ->assertVisible('.btn-edit')
                 ->assertVisible('.btn-hapus')
                 ->screenshot('tc12-detail-jadwal');
        });
    }

    /** @test TC-13: Membuka form edit jadwal */
    public function test_tc13_membuka_form_edit_jadwal(): void
    {
        $jadwal = $this->createJadwal();

        $this->browse(function (Browser $browser) use ($jadwal) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-edit')
                 ->waitForLocation("/jadwal/{$jadwal->id}/edit", 5)
                 ->assertPathIs("/jadwal/{$jadwal->id}/edit")
                 ->assertInputValue('input[name="jenis_pemeriksaan"]', 'Pemeriksaan Umum')
                 ->screenshot('tc13-form-edit-jadwal');
        });
    }

    /** @test TC-14: Mengubah tanggal pemeriksaan */
    public function test_tc14_mengubah_tanggal_pemeriksaan(): void
    {
        $jadwal      = $this->createJadwal();
        $tanggalBaru = now()->addDays(14)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="tanggal"]', 5);

            $browser->script("document.querySelector('input[name=\"tanggal\"]').value = '{$tanggalBaru}';");

            $actual = $browser->value('input[name="tanggal"]');
            $this->assertEquals($tanggalBaru, $actual);

            $browser->screenshot('tc14-mengubah-tanggal');
        });
    }

    /** @test TC-15: Mengubah waktu pemeriksaan */
    public function test_tc15_mengubah_waktu_pemeriksaan(): void
    {
        $jadwal = $this->createJadwal(['waktu' => '09:00']);

        $this->browse(function (Browser $browser) use ($jadwal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="waktu"]', 5)
                 ->clear('input[name="waktu"]')
                 ->type('input[name="waktu"]', '14:00')
                 ->assertInputValue('input[name="waktu"]', '14:00')
                 ->screenshot('tc15-mengubah-waktu');
        });
    }

    /** @test TC-16: Validasi slot baru saat edit jadwal */
    public function test_tc16_validasi_slot_saat_edit(): void
    {
        $jadwal      = $this->createJadwal();
        $tanggalBaru = now()->addDays(10)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="tanggal"]', 5);

            $browser->script("document.querySelector('input[name=\"tanggal\"]').value = '{$tanggalBaru}';");

            $actualTanggal = $browser->value('input[name="tanggal"]');
            $this->assertEquals($tanggalBaru, $actualTanggal);

            $browser->clear('input[name="waktu"]')
                    ->type('input[name="waktu"]', '15:00')
                    ->assertInputValue('input[name="waktu"]', '15:00')
                    ->screenshot('tc16-validasi-slot-saat-edit');
        });
    }

    /** @test TC-17: Memperbarui jadwal pemeriksaan ke database */
    public function test_tc17_memperbarui_jadwal(): void
    {
        $jadwal      = $this->createJadwal(['jenis_pemeriksaan' => 'Cek Tekanan Darah']);
        $tanggalBaru = now()->addDays(5)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->clear('input[name="jenis_pemeriksaan"]')
                 ->type('input[name="jenis_pemeriksaan"]', 'Cek Gula Darah')
                 ->clear('input[name="tanggal"]')
                 ->type('input[name="tanggal"]', $tanggalBaru)
                 ->clear('input[name="waktu"]')
                 ->type('input[name="waktu"]', '11:00')
                 ->click('.btn-simpan')
                 ->waitForLocation('/jadwal', 5)
                 ->assertPathIs('/jadwal')
                 ->screenshot('tc17-memperbarui-jadwal');
        });

        $this->assertDatabaseHas('jadwal', [
            'id'                => $jadwal->id,
            'jenis_pemeriksaan' => 'Cek Gula Darah',
        ]);
    }

    /** @test TC-18: Menampilkan notifikasi berhasil setelah update */
    public function test_tc18_notifikasi_update_berhasil(): void
    {
        $jadwal      = $this->createJadwal();
        $tanggalBaru = now()->addDays(6)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->clear('input[name="tanggal"]')
                 ->type('input[name="tanggal"]', $tanggalBaru)
                 ->clear('input[name="waktu"]')
                 ->type('input[name="waktu"]', '13:00')
                 ->click('.btn-simpan')
                 ->waitForLocation('/jadwal', 5)
                 ->waitFor('#successModal.active', 5)
                 ->assertSee('Jadwal berhasil diperbarui')
                 ->screenshot('tc18-notifikasi-update-berhasil');
        });
    }

    /** @test TC-19: Menghapus jadwal - tombol Hapus tersedia */
    public function test_tc19_tombol_hapus_jadwal_tersedia(): void
    {
        $this->createJadwal(['jenis_pemeriksaan' => 'Jadwal Akan Dihapus']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->assertSee('Jadwal Akan Dihapus')
                 ->assertVisible('.btn-hapus')
                 ->screenshot('tc19-tombol-hapus-tersedia');
        });
    }

    /** @test TC-20: Menampilkan dialog konfirmasi hapus */
    public function test_tc20_dialog_konfirmasi_hapus(): void
    {
        $this->createJadwal();

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->screenshot('tc20-before-hapus');

            // Klik hapus → native confirm() dialog muncul
            $browser->click('.btn-hapus');

            // Ambil teks dialog sebelum dismiss
            $dialogText = $browser->driver->switchTo()->alert()->getText();

            $this->assertStringContainsString('Hapus jadwal ini', $dialogText);

            $browser->dismissDialog()
                    ->screenshot('tc20-dialog-konfirmasi-hapus-dismissed');
        });
    }

    /** @test TC-21: Konfirmasi hapus jadwal - data terhapus dari database */
    public function test_tc21_konfirmasi_hapus_jadwal(): void
    {
        $jadwal = $this->createJadwal(['jenis_pemeriksaan' => 'Jadwal Hapus Konfirm']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-hapus')
                 ->acceptDialog()
                 ->waitForLocation('/jadwal', 5)
                 ->screenshot('tc21-konfirmasi-hapus-jadwal');
        });

        $this->assertDatabaseMissing('jadwal', ['id' => $jadwal->id]);
    }

    /** @test TC-22: Menampilkan notifikasi hapus berhasil */
    public function test_tc22_notifikasi_hapus_berhasil(): void
    {
        $this->createJadwal();

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-hapus')
                 ->acceptDialog()
                 ->waitForLocation('/jadwal', 5)
                 ->waitFor('#successModal.active', 5)
                 ->assertSee('Jadwal berhasil dihapus')
                 ->screenshot('tc22-notifikasi-hapus-berhasil');
        });
    }

    /** @test TC-23: Membatalkan proses hapus - data tetap tersimpan */
    public function test_tc23_membatalkan_hapus_jadwal(): void
    {
        $jadwal = $this->createJadwal(['jenis_pemeriksaan' => 'Jadwal Tetap Ada']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-hapus')
                 ->dismissDialog()
                 ->assertPathIs('/jadwal')
                 ->assertSee('Jadwal Tetap Ada')
                 ->screenshot('tc23-batal-hapus-jadwal');
        });

        $this->assertDatabaseHas('jadwal', ['id' => $jadwal->id]);
    }

    /** @test TC-24: Edit jadwal yang sudah lewat (status selesai) */
    public function test_tc24_edit_jadwal_status_selesai(): void
    {
        $jadwal = $this->createJadwal([
            'jenis_pemeriksaan' => 'Jadwal Sudah Selesai',
            'tanggal'           => now()->subDays(3)->format('Y-m-d'),
            'status'            => 'selesai',
        ]);

        $this->browse(function (Browser $browser) use ($jadwal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->assertPathIs("/jadwal/{$jadwal->id}/edit")
                 ->assertInputValue('input[name="jenis_pemeriksaan"]', 'Jadwal Sudah Selesai')
                 ->screenshot('tc24-edit-jadwal-status-selesai');
        });
    }

    /** @test TC-25: Menampilkan data jadwal terbaru setelah perubahan */
    public function test_tc25_menampilkan_data_jadwal_terbaru(): void
    {
        $jadwal      = $this->createJadwal(['jenis_pemeriksaan' => 'Data Awal PKE5']);
        $tanggalBaru = now()->addDays(4)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->clear('input[name="jenis_pemeriksaan"]')
                 ->type('input[name="jenis_pemeriksaan"]', 'Data Terbaru PKE5')
                 ->clear('input[name="tanggal"]')
                 ->type('input[name="tanggal"]', $tanggalBaru)
                 ->clear('input[name="waktu"]')
                 ->type('input[name="waktu"]', '16:00')
                 ->click('.btn-simpan')
                 ->waitForLocation('/jadwal', 5)
                 ->waitFor('#successModal.active', 5)
                 ->click('.btn-continue')
                 ->waitFor('.jadwal-card', 5)
                 ->assertSee('Data Terbaru PKE5')
                 ->assertDontSee('Data Awal PKE5')
                 ->screenshot('tc25-data-jadwal-terbaru');
        });
    }
}
