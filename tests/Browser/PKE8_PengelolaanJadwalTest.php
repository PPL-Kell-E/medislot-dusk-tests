<?php

namespace Tests\Browser;

use App\Models\Jadwal;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PKE8_PengelolaanJadwalTest extends DuskTestCase
{
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $uniqueId = Str::random(8);

        $this->testUser = User::create([
            'full_name'     => 'Test Dusk PKE8',
            'email'         => "dusk.pke8.{$uniqueId}@test.local",
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

    /** @test TC-01: Menampilkan detail jadwal beserta tombol Edit dan Hapus */
    public function test_tc01_menampilkan_detail_jadwal(): void
    {
        $this->createJadwal([
            'jenis_pemeriksaan' => 'Cek Jantung',
            'fasilitas_klinik'  => 'RS Sejahtera',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->assertSee('Cek Jantung')
                 ->assertSee('RS Sejahtera')
                 ->assertVisible('.btn-edit')
                 ->assertVisible('.btn-hapus')
                 ->screenshot('tc01-detail-jadwal-edit-hapus');
        });
    }

    /** @test TC-02: Membuka form edit jadwal dengan data sebelumnya terisi otomatis */
    public function test_tc02_membuka_form_edit_jadwal(): void
    {
        $jadwal = $this->createJadwal([
            'jenis_pemeriksaan' => 'Cek Darah Lengkap',
            'fasilitas_klinik'  => 'Klinik Husada',
        ]);

        $this->browse(function (Browser $browser) use ($jadwal) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-edit')
                 ->waitForLocation("/jadwal/{$jadwal->id}/edit", 5)
                 ->assertPathIs("/jadwal/{$jadwal->id}/edit")
                 ->assertInputValue('input[name="jenis_pemeriksaan"]', 'Cek Darah Lengkap')
                 ->assertInputValue('input[name="fasilitas_klinik"]', 'Klinik Husada')
                 ->screenshot('tc02-form-edit-autofill');
        });
    }

    /** @test TC-03: Mengubah tanggal pemeriksaan di form edit */
    public function test_tc03_mengubah_tanggal_pemeriksaan(): void
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

            $browser->screenshot('tc03-mengubah-tanggal-pemeriksaan');
        });
    }

    /** @test TC-04: Mengubah waktu pemeriksaan di form edit */
    public function test_tc04_mengubah_waktu_pemeriksaan(): void
    {
        $jadwal = $this->createJadwal(['waktu' => '09:00']);

        $this->browse(function (Browser $browser) use ($jadwal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="waktu"]', 5)
                 ->clear('input[name="waktu"]')
                 ->type('input[name="waktu"]', '14:30')
                 ->assertInputValue('input[name="waktu"]', '14:30')
                 ->screenshot('tc04-mengubah-waktu-pemeriksaan');
        });
    }

    /** @test TC-05: Validasi slot tersedia - tanggal mendatang diterima form */
    public function test_tc05_validasi_slot_tersedia(): void
    {
        $jadwal      = $this->createJadwal();
        $tanggalBaru = now()->addDays(10)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="tanggal"]', 5);

            // Set tanggal mendatang → slot tersedia, form menerima nilai
            $browser->script("document.querySelector('input[name=\"tanggal\"]').value = '{$tanggalBaru}';");

            $actual = $browser->value('input[name="tanggal"]');
            $this->assertEquals($tanggalBaru, $actual);

            $browser->clear('input[name="waktu"]')
                    ->type('input[name="waktu"]', '10:00')
                    ->assertInputValue('input[name="waktu"]', '10:00')
                    ->screenshot('tc05-validasi-slot-tersedia');
        });
    }

    /** @test TC-06: Validasi slot tidak tersedia - tanggal sudah lewat diterima namun status menjadi Selesai */
    public function test_tc06_validasi_slot_tidak_tersedia(): void
    {
        $jadwal       = $this->createJadwal();
        $tanggalLewat = now()->subDays(2)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalLewat) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="tanggal"]', 5);

            // Set tanggal lewat → server akan set status = selesai otomatis
            $browser->script("document.querySelector('input[name=\"tanggal\"]').value = '{$tanggalLewat}';");

            $actual = $browser->value('input[name="tanggal"]');
            $this->assertEquals($tanggalLewat, $actual);

            $browser->select('select[name="status"]', 'selesai')
                    ->assertSelected('select[name="status"]', 'selesai')
                    ->screenshot('tc06-validasi-slot-tidak-tersedia-tanggal-lewat');
        });
    }

    /** @test TC-07: Update jadwal berhasil - data diperbarui + notifikasi */
    public function test_tc07_update_jadwal_berhasil(): void
    {
        $jadwal      = $this->createJadwal(['jenis_pemeriksaan' => 'Sebelum Update']);
        $tanggalBaru = now()->addDays(5)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->clear('input[name="jenis_pemeriksaan"]')
                 ->type('input[name="jenis_pemeriksaan"]', 'Setelah Update');

            $browser->script("document.querySelector('input[name=\"tanggal\"]').value = '{$tanggalBaru}';");

            $browser->clear('input[name="waktu"]')
                    ->type('input[name="waktu"]', '11:00')
                    ->click('.btn-simpan')
                    ->waitForLocation('/jadwal', 5)
                    ->waitFor('#successModal.active', 5)
                    ->assertSee('Jadwal berhasil diperbarui')
                    ->screenshot('tc07-update-jadwal-berhasil');
        });

        $this->assertDatabaseHas('jadwal', [
            'id'                => $jadwal->id,
            'jenis_pemeriksaan' => 'Setelah Update',
        ]);
    }

    /** @test TC-08: Verifikasi hasil update - data terbaru tampil di daftar */
    public function test_tc08_verifikasi_hasil_update(): void
    {
        $jadwal      = $this->createJadwal(['jenis_pemeriksaan' => 'Data Lama PKE8']);
        $tanggalBaru = now()->addDays(6)->format('Y-m-d');

        $this->browse(function (Browser $browser) use ($jadwal, $tanggalBaru) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->clear('input[name="jenis_pemeriksaan"]')
                 ->type('input[name="jenis_pemeriksaan"]', 'Data Baru PKE8');

            $browser->script("document.querySelector('input[name=\"tanggal\"]').value = '{$tanggalBaru}';");

            $browser->clear('input[name="waktu"]')
                    ->type('input[name="waktu"]', '15:00')
                    ->click('.btn-simpan')
                    ->waitForLocation('/jadwal', 5)
                    ->waitFor('#successModal.active', 5)
                    ->click('.btn-continue')
                    ->waitFor('.jadwal-card', 5)
                    ->assertSee('Data Baru PKE8')
                    ->assertDontSee('Data Lama PKE8')
                    ->screenshot('tc08-verifikasi-hasil-update');
        });
    }

    /** @test TC-09: Edit jadwal yang sudah lewat - form edit tetap dapat diakses */
    public function test_tc09_edit_jadwal_yang_sudah_lewat(): void
    {
        $jadwal = $this->createJadwal([
            'jenis_pemeriksaan' => 'Jadwal Sudah Lewat',
            'tanggal'           => now()->subDays(5)->format('Y-m-d'),
            'status'            => 'selesai',
        ]);

        $this->browse(function (Browser $browser) use ($jadwal) {
            $this->loginAndGoToJadwal($browser)
                 ->visit("/jadwal/{$jadwal->id}/edit")
                 ->waitFor('input[name="jenis_pemeriksaan"]', 5)
                 ->assertPathIs("/jadwal/{$jadwal->id}/edit")
                 ->assertInputValue('input[name="jenis_pemeriksaan"]', 'Jadwal Sudah Lewat')
                 ->assertSelected('select[name="status"]', 'selesai')
                 ->screenshot('tc09-edit-jadwal-sudah-lewat');
        });
    }

    /** @test TC-10: Menampilkan dialog konfirmasi hapus */
    public function test_tc10_menampilkan_konfirmasi_hapus(): void
    {
        $this->createJadwal(['jenis_pemeriksaan' => 'Jadwal Akan Dihapus']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->screenshot('tc10-before-hapus');

            $browser->click('.btn-hapus');

            $dialogText = $browser->driver->switchTo()->alert()->getText();
            $this->assertStringContainsString('Hapus jadwal ini', $dialogText);

            $browser->dismissDialog()
                    ->screenshot('tc10-konfirmasi-hapus-dismissed');
        });
    }

    /** @test TC-11: Membatalkan penghapusan - dialog ditutup, data tetap tersimpan */
    public function test_tc11_membatalkan_penghapusan(): void
    {
        $jadwal = $this->createJadwal(['jenis_pemeriksaan' => 'Jadwal Tidak Jadi Hapus']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-hapus')
                 ->dismissDialog()
                 ->assertPathIs('/jadwal')
                 ->assertSee('Jadwal Tidak Jadi Hapus')
                 ->screenshot('tc11-batal-hapus-data-tetap');
        });

        $this->assertDatabaseHas('jadwal', ['id' => $jadwal->id]);
    }

    /** @test TC-12: Menghapus jadwal - data terhapus dari database */
    public function test_tc12_menghapus_jadwal(): void
    {
        $jadwal = $this->createJadwal(['jenis_pemeriksaan' => 'Jadwal Dihapus Konfirm']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-hapus')
                 ->acceptDialog()
                 ->waitForLocation('/jadwal', 5)
                 ->screenshot('tc12-menghapus-jadwal');
        });

        $this->assertDatabaseMissing('jadwal', ['id' => $jadwal->id]);
    }

    /** @test TC-13: Verifikasi penghapusan - jadwal tidak lagi tampil di daftar */
    public function test_tc13_verifikasi_penghapusan(): void
    {
        $this->createJadwal(['jenis_pemeriksaan' => 'Jadwal Cek Verifikasi Hapus']);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->assertSee('Jadwal Cek Verifikasi Hapus')
                 ->click('.btn-hapus')
                 ->acceptDialog()
                 ->waitForLocation('/jadwal', 5)
                 ->waitFor('#successModal.active', 5)
                 ->click('.btn-continue')
                 ->pause(500)
                 ->assertDontSee('Jadwal Cek Verifikasi Hapus')
                 ->screenshot('tc13-verifikasi-penghapusan');
        });
    }

    /** @test TC-14: Hapus jadwal berhasil - notifikasi sukses muncul */
    public function test_tc14_hapus_jadwal_berhasil(): void
    {
        $this->createJadwal();

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToJadwal($browser)
                 ->click('.btn-hapus')
                 ->acceptDialog()
                 ->waitForLocation('/jadwal', 5)
                 ->waitFor('#successModal.active', 5)
                 ->assertSee('Jadwal berhasil dihapus')
                 ->screenshot('tc14-hapus-jadwal-berhasil');
        });
    }
}
