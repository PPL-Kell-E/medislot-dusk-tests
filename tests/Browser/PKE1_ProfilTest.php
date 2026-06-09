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

class PKE1_ProfilTest extends DuskTestCase
{
    protected User $testUser;
    protected Profile $testProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $uniqueId = Str::random(8);

        $this->testUser = User::create([
            'full_name'     => 'Test Dusk PKE1',
            'email'         => "dusk.pke1.{$uniqueId}@test.local",
            'password_hash' => Hash::make('Password123!'),
            'role'          => 'user',
        ]);

        $this->testProfile = Profile::create([
            'id'      => $this->testUser->id,
            'name'    => 'Budi Santoso',
            'age'     => 30,
            'gender'  => 'Laki-laki',
            'phone'   => '081234567890',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
        ]);
    }

    protected function tearDown(): void
    {
        HealthData::where('user_id', $this->testUser->id)->delete();
        Profile::destroy($this->testUser->id);
        $this->testUser->delete();

        parent::tearDown();
    }

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

    /** @test TC-01: Akses halaman profil */
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

    /** @test TC-02: Data profil ditampilkan */
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

    /** @test TC-03: Mode read-only saat pertama dibuka */
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

    /** @test TC-04: Profil tidak lengkap tampil tanpa error */
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

    /** @test TC-05: Klik Edit, form muncul */
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

    /** @test TC-06: Auto-fill data saat masuk mode edit */
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
                 ->assertInputValue('@inputName', 'Siti Rahayu')
                 ->assertInputValue('@inputAge', '28')
                 ->assertSelected('@selectGender', 'Perempuan')
                 ->screenshot('tc06-autofill-edit-mode');
        });
    }

    /** @test TC-07: Input valid diterima */
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

    /** @test TC-10: Usia non-angka ditolak browser */
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

    /** @test TC-15: Simpan valid tersimpan ke database */
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

    /** @test TC-16: Tidak simpan, data tidak berubah */
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

    /** @test TC-17: Notifikasi sukses muncul setelah simpan */
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

    /** @test TC-18: Data terupdate setelah refresh */
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
