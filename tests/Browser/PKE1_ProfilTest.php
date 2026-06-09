<?php

namespace Tests\Browser;

use App\Models\HealthData;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Pages\ProfilePage;
use Tests\DuskTestCase;

/**
 * ═══════════════════════════════════════════════════════════
 * PKE-1 — Pengelolaan Profil Pasien
 * Subtask: PKE-22 (Lihat Profil) · PKE-23 (Ubah Profil)
 * ═══════════════════════════════════════════════════════════
 *
 * Pola testing:
 *  - setUp()    : buat user + profil unik di DB shared (database.sqlite)
 *  - tearDown() : hapus user + profil agar data bersih
 *  - loginAs()  : inject session langsung — tidak perlu klik form login
 *
 * Seluruh 18 TC dari tabel acceptance criteria PKE-1:
 *
 *  TC-01  Akses halaman profil
 *  TC-02  Data ditampilkan di view mode
 *  TC-03  Mode read-only saat pertama dibuka
 *  TC-04  Data kosong / edge case (profil belum lengkap)
 *  TC-05  Klik "Edit" → form muncul
 *  TC-06  Auto-fill data saat masuk mode edit
 *  TC-07  Input valid (Nama + Usia positif)
 *  TC-08  Nama kosong → error mandatory
 *  TC-09  Usia kosong → error mandatory
 *  TC-10  Usia non-angka → ditolak oleh browser (type=number)
 *  TC-11  Usia negatif → error validasi server
 *  TC-12  Usia nol → error validasi server
 *  TC-13  Usia desimal → error validasi server (rule: integer)
 *  TC-14  Jenis kelamin tidak dipilih → error
 *  TC-15  Simpan data valid → tersimpan ke database
 *  TC-16  Tidak klik Simpan → data tidak berubah
 *  TC-17  Notifikasi sukses muncul setelah simpan
 *  TC-18  Data ter-update setelah refresh
 */
class PKE1_ProfilTest extends DuskTestCase
{
    /** @var User User yang dibuat untuk setiap test */
    protected User $testUser;

    /** @var Profile Profil awal yang dibuat untuk setiap test */
    protected Profile $testProfile;

    // ─────────────────────────────────────────────────────────
    //  SETUP & TEARDOWN
    // ─────────────────────────────────────────────────────────

    /**
     * Buat user + profil unik sebelum setiap test.
     * Menggunakan email unik agar tidak bentrok antar test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $uniqueId = Str::random(8);

        $this->testUser = User::create([
            'full_name'    => 'Test Dusk PKE1',
            'email'        => "dusk.pke1.{$uniqueId}@test.local",
            'password_hash'=> Hash::make('Password123!'),
            'role'         => 'user',
        ]);

        $this->testProfile = Profile::create([
            'id'     => $this->testUser->id,
            'name'   => 'Budi Santoso',
            'age'    => 30,
            'gender' => 'Laki-laki',
            'phone'  => '081234567890',
            'address'=> 'Jl. Sudirman No. 1, Jakarta',
        ]);
    }

    /**
     * Bersihkan user + profil + health data setelah setiap test.
     */
    protected function tearDown(): void
    {
        HealthData::where('user_id', $this->testUser->id)->delete();
        Profile::destroy($this->testUser->id);
        $this->testUser->delete();

        parent::tearDown();
    }

    // ─────────────────────────────────────────────────────────
    //  HELPER
    // ─────────────────────────────────────────────────────────

    /**
     * Login via form (email + password) lalu buka halaman profil.
     * Menggunakan form login nyata agar session terbentuk dengan benar di Chrome.
     */
    private function loginAndGoToProfile(Browser $browser): Browser
    {
        return $browser->visit('/login')
                       ->waitFor('input[name="email"]', 5)
                       ->type('input[name="email"]', $this->testUser->email)
                       ->type('input[name="password"]', 'Password123!')
                       ->press('Sign In')
                       ->waitForLocation('/dashboard', 5)
                       ->visit('/profile')
                       ->waitFor('#viewMode', 5);
    }

    // ─────────────────────────────────────────────────────────
    //  TC-01 · Akses halaman profil
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-01: User sudah login → navigasi ke /profile → halaman profil tampil
     *        dengan judul "Profil Saya".
     */
    public function test_tc01_akses_halaman_profil(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->assertSee('Profil Saya')
                 ->assertPathIs('/profile')
                 ->screenshot('tc01-akses-halaman-profil');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-02 · Data profil ditampilkan
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-02: Halaman profil menampilkan Nama, Usia, dan Jenis Kelamin
     *        sesuai data yang tersimpan di database.
     */
    public function test_tc02_data_profil_tampil(): void
    {
        $this->testProfile->update([
            'name'   => 'Andi Wijaya',
            'age'    => 25,
            'gender' => 'Laki-laki',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->assertSee('Andi Wijaya')
                 ->assertSee('25')
                 ->assertSee('Laki-laki')
                 ->screenshot('tc02-data-profil-tampil');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-03 · Mode read-only saat pertama dibuka
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-03: Halaman profil pertama kali dibuka dalam mode view (read-only).
     *        #viewMode terlihat, #editMode tersembunyi (display:none),
     *        tombol "Edit" tersedia di topbar.
     */
    public function test_tc03_mode_read_only_saat_dibuka(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser);

            $browser->assertVisible('#viewMode')
                    ->assertMissing('#editMode')
                    ->assertVisible('#topbarBtnEdit')
                    ->screenshot('tc03-mode-read-only');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-04 · Edge case: data profil kosong / belum lengkap
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-04: Profil dengan field opsional kosong (phone, address, birth_date)
     *        tetap ditampilkan tanpa 500 error — field kosong tampil sebagai '-'.
     */
    public function test_tc04_profil_tidak_lengkap_tampil_tanpa_error(): void
    {
        $this->testProfile->update([
            'phone'      => null,
            'address'    => null,
            'birth_date' => null,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->assertDontSee('500')
                 ->assertDontSee('Whoops')
                 ->assertSee('-')
                 ->screenshot('tc04-profil-tidak-lengkap');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-05 · Klik "Edit" → form muncul
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-05: Klik tombol "Edit" di topbar → toggleEditMode() dipanggil →
     *        #editMode tampil, #viewMode tersembunyi.
     */
    public function test_tc05_klik_ubah_profil_form_muncul(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->assertInEditMode($browser)
                 ->screenshot('tc05-form-edit-muncul');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-06 · Auto-fill data saat masuk mode edit
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-06: Ketika masuk mode edit, field-field form sudah terisi
     *        dengan data profil yang ada sebelumnya.
     */
    public function test_tc06_autofill_saat_masuk_edit_mode(): void
    {
        $this->testProfile->update([
            'name'   => 'Siti Rahayu',
            'age'    => 28,
            'gender' => 'Perempuan',
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->assertInputValue('@inputName',  'Siti Rahayu')
                 ->assertInputValue('@inputAge',   '28')
                 ->assertSelected('@selectGender', 'Perempuan')
                 ->screenshot('tc06-autofill-edit-mode');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-07 · Input valid — data diterima
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-07: Mengisi Nama="Budi", Usia=21, Jenis Kelamin="Laki-laki"
     *        lalu klik Simpan → request diterima tanpa error validasi,
     *        redirect kembali ke /profile.
     */
    public function test_tc07_input_valid_data_diterima(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->clear('@inputName')
                 ->type('@inputName', 'Budi')
                 ->clear('@inputAge')
                 ->type('@inputAge', '21')
                 ->select('@selectGender', 'Laki-laki')
                 ->screenshot('tc07-before-submit-valid');

            (new ProfilePage)->clickSave($browser);

            $browser->assertPathIs('/profile')
                    ->assertDontSee('wajib diisi')
                    ->screenshot('tc07-input-valid-diterima');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-08 · Nama kosong → error mandatory
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-08: Mengosongkan field Nama lalu klik Simpan →
     *        muncul pesan "Nama lengkap wajib diisi."
     */
    public function test_tc08_nama_kosong_error_mandatory(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser);

            $browser->script("document.querySelector('input[name=\"name\"]').removeAttribute('required')");
            $browser->waitFor('input[name="name"]', 5)
                 ->clear('input[name="name"]')
                 ->type('input[name="age"]', '25')
                 ->select('select[name="gender"]', 'Laki-laki');
            $browser->click('button[form="profileForm"]');
            $browser->assertSee('Nama lengkap wajib diisi.')
                    ->screenshot('tc08-nama-kosong-error');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-09 · Usia kosong → error mandatory
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-09: Mengosongkan field Usia lalu klik Simpan →
     *        muncul pesan "Usia wajib diisi."
     */
    public function test_tc09_usia_kosong_error_mandatory(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->type('@inputName', 'Budi');

            $browser->script("
                const ageInput = document.querySelector('input[name=\"age\"]');
                ageInput.removeAttribute('required');
                ageInput.removeAttribute('min');
            ");
            $browser->waitFor('input[name="age"]', 5)
                 ->clear('input[name="age"]')
                 ->select('select[name="gender"]', 'Laki-laki');
            $browser->click('button[form="profileForm"]');
            $browser->assertSee('Usia wajib diisi.')
                    ->screenshot('tc09-usia-kosong-error');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-10 · Usia non-angka → ditolak oleh browser (type="number")
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-10: Input type="number" membuang karakter non-numerik.
     *        Ketik "abc" → value field tetap kosong atau tidak berubah.
     */
    public function test_tc10_usia_non_angka_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->clear('@inputAge')
                 ->type('@inputAge', 'abc');

            $ageValue = $browser->value('@inputAge');

            $browser->screenshot('tc10-usia-non-angka-ditolak');

            $this->assertEmpty(
                $ageValue,
                "Input type=number seharusnya menolak 'abc', namun mendapat: '{$ageValue}'"
            );
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-11 · Usia negatif → ditolak server (min:1)
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-11: Usia = -5 → server mengembalikan error validasi
     *        "Usia harus berupa angka positif."
     */
    public function test_tc11_usia_negatif_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->type('@inputName', 'Budi');

            $browser->script("document.querySelector('input[name=\"age\"]').removeAttribute('min')");
            $browser->pause(300)
                 ->clear('@inputAge')
                 ->type('@inputAge', '-5')
                 ->select('@selectGender', 'Laki-laki');
            (new ProfilePage)->clickSave($browser);
            $browser->waitForText('Usia harus berupa angka positif.', 5)
                    ->assertSee('Usia harus berupa angka positif.')
                    ->screenshot('tc11-usia-negatif-error');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-12 · Usia nol → ditolak server (min:1)
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-12: Usia = 0 → server mengembalikan error validasi
     *        "Usia harus berupa angka positif."
     */
    public function test_tc12_usia_nol_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->type('@inputName', 'Budi');

            $browser->script("document.querySelector('input[name=\"age\"]').removeAttribute('min')");
            $browser->clear('@inputAge')
                 ->type('@inputAge', '0')
                 ->select('@selectGender', 'Laki-laki');
            (new ProfilePage)->clickSave($browser);
            $browser->assertSee('Usia harus berupa angka positif.')
                    ->screenshot('tc12-usia-nol-error');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-13 · Usia desimal → ditolak server (rule: integer)
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-13: Usia = 21.5 → server menolak karena rule 'integer'.
     *        Halaman dikembalikan dengan pesan error validasi.
     */
    public function test_tc13_usia_desimal_ditolak(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->type('@inputName', 'Budi');

            $browser->script("document.querySelector('input[name=\"age\"]').type = 'text'");
            $browser->clear('@inputAge')
                 ->type('@inputAge', '21.5')
                 ->select('@selectGender', 'Laki-laki');
            (new ProfilePage)->clickSave($browser);

            $browser->pause(500);

            $pageSource = $browser->driver->getPageSource();
            $hasError = str_contains($pageSource, 'integer')
                || str_contains($pageSource, 'bilangan bulat')
                || str_contains($pageSource, 'harus berupa angka')
                || str_contains($pageSource, 'wajib diisi')
                || str_contains($pageSource, 'must be an integer')
                || str_contains($pageSource, 'must be a number');

            $browser->screenshot('tc13-usia-desimal-error');

            $this->assertTrue(
                $hasError,
                'Seharusnya ada pesan error validasi untuk usia desimal 21.5'
            );
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-14 · Jenis kelamin tidak dipilih → error
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-14: Jenis kelamin dipilih sebagai kosong (-- Pilih --) lalu
     *        klik Simpan → muncul pesan "Jenis kelamin wajib dipilih."
     */
    public function test_tc14_jenis_kelamin_tidak_dipilih_error(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->type('@inputName', 'Budi')
                 ->type('@inputAge', '25')
                 ->select('@selectGender', '');

            $browser->script("document.querySelector('select[name=\"gender\"]').removeAttribute('required')");
            $browser->pause(300);
            (new ProfilePage)->clickSave($browser);
            $browser->waitForText('Jenis kelamin wajib dipilih.', 5)
                    ->assertSee('Jenis kelamin wajib dipilih.')
                    ->screenshot('tc14-gender-kosong-error');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-15 · Simpan data valid → tersimpan ke database
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-15: Klik Simpan dengan semua field valid → data benar-benar
     *        tersimpan ke tabel profiles (verifikasi langsung ke DB).
     */
    public function test_tc15_simpan_valid_tersimpan_ke_database(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->clear('@inputName')
                 ->type('@inputName', 'Citra Dewi')
                 ->clear('@inputAge')
                 ->type('@inputAge', '32')
                 ->select('@selectGender', 'Perempuan')
                 ->clickSave($browser)
                 ->assertPathIs('/profile')
                 ->screenshot('tc15-simpan-valid-ke-database');
        });

        $this->assertDatabaseHas('profiles', [
            'id'     => $this->testUser->id,
            'name'   => 'Citra Dewi',
            'age'    => 32,
            'gender' => 'Perempuan',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  TC-16 · Tidak klik Simpan → data tidak berubah
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-16: User mengganti data di form lalu klik "Batal" (tanpa Simpan)
     *        → data di database tetap seperti semula.
     */
    public function test_tc16_tidak_simpan_data_tidak_berubah(): void
    {
        $this->testProfile->update([
            'name' => 'Nama Asli',
            'age'  => 40,
        ]);

        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->clear('@inputName')
                 ->type('@inputName', 'Nama Diubah Sementara')
                 ->screenshot('tc16-before-cancel')
                 ->clickCancel($browser)
                 ->assertInViewMode($browser)
                 ->screenshot('tc16-setelah-batal-view-mode');
        });

        $this->assertDatabaseHas('profiles', [
            'id'   => $this->testUser->id,
            'name' => 'Nama Asli',
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  TC-17 · Notifikasi sukses muncul setelah simpan
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-17: Setelah menyimpan data valid, flash message
     *        "Profil berhasil diperbarui" tampil di halaman profil.
     */
    public function test_tc17_notifikasi_sukses_muncul(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->clear('@inputName')
                 ->type('@inputName', 'Dedi Kurnia')
                 ->clear('@inputAge')
                 ->type('@inputAge', '45')
                 ->select('@selectGender', 'Laki-laki')
                 ->clickSave($browser)
                 ->waitForLocation('/profile', 5)
                 ->waitForText('Profil berhasil diperbarui', 5)
                 ->screenshot('tc17-notifikasi-sukses');
        });
    }

    // ─────────────────────────────────────────────────────────
    //  TC-18 · Data ter-update setelah refresh
    // ─────────────────────────────────────────────────────────

    /**
     * @test
     * TC-18: Setelah simpan berhasil, refresh halaman →
     *        data terbaru tampil di view mode (bukan data lama).
     */
    public function test_tc18_data_terupdate_setelah_refresh(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToProfile($browser)
                 ->on(new ProfilePage)
                 ->clickEdit($browser)
                 ->clear('@inputName')
                 ->type('@inputName', 'Eka Putri')
                 ->clear('@inputAge')
                 ->type('@inputAge', '19')
                 ->select('@selectGender', 'Perempuan')
                 ->clickSave($browser)
                 ->waitForLocation('/profile', 5)
                 ->waitForText('Profil berhasil diperbarui', 5);

            $browser->refresh()
                    ->waitFor('#viewMode', 5)
                    ->assertSee('Eka Putri')
                    ->assertSee('19')
                    ->assertSee('Perempuan')
                    ->screenshot('tc18-data-terupdate-setelah-refresh');
        });
    }
}
