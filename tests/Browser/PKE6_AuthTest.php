<?php

namespace Tests\Browser;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PKE6_AuthTest extends DuskTestCase
{
    protected User $testUser;
    protected string $testEmail;
    protected string $newUserEmail;

    protected function setUp(): void
    {
        parent::setUp();

        $uniqueId = Str::random(8);

        // User untuk TC login/logout dan TC-03 duplicate email
        $this->testEmail = "dusk.pke6.{$uniqueId}@test.local";
        $this->testUser  = User::create([
            'full_name'     => 'Test Dusk PKE6',
            'email'         => $this->testEmail,
            'password_hash' => Hash::make('Password123!'),
            'role'          => 'user',
        ]);

        // Email unik untuk TC-02 (registrasi baru)
        $this->newUserEmail = "dusk.pke6.new.{$uniqueId}@test.local";
    }

    protected function tearDown(): void
    {
        // Hapus user yang dibuat saat TC-02 (registrasi)
        $newUser = User::where('email', $this->newUserEmail)->first();
        if ($newUser) {
            Profile::where('id', $newUser->id)->delete();
            $newUser->delete();
        }

        Profile::where('id', $this->testUser->id)->delete();
        $this->testUser->delete();

        parent::tearDown();
    }

    // =========================================================
    // REGISTRASI
    // =========================================================

    /** @test TC-01: Akses halaman registrasi */
    public function test_tc01_akses_halaman_registrasi(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->waitFor('.auth-form', 5)
                    ->assertPathIs('/register')
                    ->assertVisible('input[name="full_name"]')
                    ->assertVisible('input[name="email"]')
                    ->assertVisible('input[name="password"]')
                    ->assertVisible('.btn-submit')
                    ->screenshot('tc01-akses-halaman-registrasi');
        });
    }

    /** @test TC-02: Input valid - akun berhasil dibuat */
    public function test_tc02_registrasi_input_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="full_name"]', 'Pengguna Baru')
                    ->type('input[name="email"]', $this->newUserEmail)
                    ->type('input[name="password"]', 'Password123!')
                    ->click('.btn-submit')
                    ->waitForLocation('/dashboard', 10)
                    ->assertPathIs('/dashboard')
                    ->screenshot('tc02-registrasi-input-valid');
        });

        $this->assertDatabaseHas('users', [
            'email' => $this->newUserEmail,
        ]);
    }

    /** @test TC-03: Email sudah terdaftar - muncul error */
    public function test_tc03_registrasi_email_sudah_terdaftar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="full_name"]', 'User Duplikat')
                    ->type('input[name="email"]', $this->testEmail) // email sudah ada di DB
                    ->type('input[name="password"]', 'Password123!')
                    ->click('.btn-submit')
                    ->waitFor('.alert.alert-error', 5)
                    ->assertVisible('.alert.alert-error')
                    ->screenshot('tc03-registrasi-email-sudah-terdaftar');
        });
    }

    /** @test TC-04: Format email tidak valid - browser menolak submit */
    public function test_tc04_registrasi_format_email_tidak_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="full_name"]', 'User Test')
                    ->type('input[name="email"]', 'user123') // bukan format email
                    ->type('input[name="password"]', 'Password123!')
                    ->click('.btn-submit')
                    ->pause(500) // browser HTML5 validation menolak, halaman tidak berpindah
                    ->assertPathIs('/register')
                    ->screenshot('tc04-registrasi-format-email-tidak-valid');
        });
    }

    /** @test TC-05: Password kosong - validasi wajib isi */
    public function test_tc05_registrasi_password_kosong(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="full_name"]', 'User Test')
                    ->type('input[name="email"]', 'test.empty.pass@test.local')
                    // password tidak diisi
                    ->click('.btn-submit')
                    ->pause(500) // browser required validation menolak submit
                    ->assertPathIs('/register')
                    ->screenshot('tc05-registrasi-password-kosong');
        });
    }

    /** @test TC-06: Password terlalu pendek (< 8 karakter) */
    public function test_tc06_registrasi_password_terlalu_pendek(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="full_name"]', 'User Test')
                    ->type('input[name="email"]', 'test.short.pass@test.local')
                    ->type('input[name="password"]', 'abc123') // hanya 6 karakter
                    ->click('.btn-submit')
                    ->waitFor('.alert.alert-error', 5)
                    ->assertVisible('.alert.alert-error')
                    ->screenshot('tc06-registrasi-password-terlalu-pendek');
        });
    }

    // =========================================================
    // LOGIN
    // =========================================================

    /** @test TC-07: Akses halaman login */
    public function test_tc07_akses_halaman_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.auth-form', 5)
                    ->assertPathIs('/login')
                    ->assertVisible('input[name="email"]')
                    ->assertVisible('input[name="password"]')
                    ->assertVisible('.btn-submit')
                    ->screenshot('tc07-akses-halaman-login');
        });
    }

    /** @test TC-08: Login valid - masuk ke dashboard */
    public function test_tc08_login_valid(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="email"]', $this->testEmail)
                    ->type('input[name="password"]', 'Password123!')
                    ->click('.btn-submit')
                    ->waitForLocation('/dashboard', 10)
                    ->assertPathIs('/dashboard')
                    ->screenshot('tc08-login-valid');
        });
    }

    /** @test TC-09: Password salah - muncul error login gagal */
    public function test_tc09_login_password_salah(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="email"]', $this->testEmail)
                    ->type('input[name="password"]', 'SalahPassword!')
                    ->click('.btn-submit')
                    ->waitFor('.alert.alert-error', 5)
                    ->assertVisible('.alert.alert-error')
                    ->assertPathIs('/login')
                    ->screenshot('tc09-login-password-salah');
        });
    }

    /** @test TC-10: Email tidak terdaftar - muncul error login gagal */
    public function test_tc10_login_email_tidak_terdaftar(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="email"]', 'tidakterdaftar@test.local')
                    ->type('input[name="password"]', 'Password123!')
                    ->click('.btn-submit')
                    ->waitFor('.alert.alert-error', 5)
                    ->assertVisible('.alert.alert-error')
                    ->assertPathIs('/login')
                    ->screenshot('tc10-login-email-tidak-terdaftar');
        });
    }

    /** @test TC-11: Field kosong - validasi muncul */
    public function test_tc11_login_field_kosong(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                    ->waitFor('.auth-form', 5)
                    // tidak isi apapun
                    ->click('.btn-submit')
                    ->pause(500) // browser required validation menolak submit
                    ->assertPathIs('/login')
                    ->screenshot('tc11-login-field-kosong');
        });
    }

    // =========================================================
    // LOGOUT
    // =========================================================

    /** @test TC-12: Logout - sesi dihapus dan kembali ke halaman login */
    public function test_tc12_logout(): void
    {
        $this->browse(function (Browser $browser) {
            // Login terlebih dahulu
            $browser->visit('/login')
                    ->waitFor('.auth-form', 5)
                    ->type('input[name="email"]', $this->testEmail)
                    ->type('input[name="password"]', 'Password123!')
                    ->click('.btn-submit')
                    ->waitForLocation('/dashboard', 10)
                    ->assertPathIs('/dashboard');

            // Klik tombol Keluar di sidebar
            $browser->click('.sidebar-footer button')
                    ->waitForLocation('/login', 5)
                    ->assertPathIs('/login')
                    ->screenshot('tc12-logout-berhasil');
        });

        // Verifikasi sesi sudah tidak valid: akses /dashboard harus redirect ke /login
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard')
                    ->waitForLocation('/login', 5)
                    ->assertPathIs('/login')
                    ->screenshot('tc12-logout-sesi-invalid');
        });
    }
}
