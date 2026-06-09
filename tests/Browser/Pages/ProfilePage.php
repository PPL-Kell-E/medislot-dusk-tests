<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

/**
 * PKE-1: Page Object untuk halaman Profil Saya
 *
 * Merepresentasikan /profile dengan dua mode:
 * - View Mode  : #viewMode tampil, #editMode tersembunyi
 * - Edit Mode  : #editMode tampil, #viewMode tersembunyi
 */
class ProfilePage extends Page
{
    /** URL halaman profil */
    public function url(): string
    {
        return '/profile';
    }

    /**
     * Selector-selector penting di halaman profil.
     * Digunakan oleh Browser::assertSee/@shorthand Dusk.
     */
    public function elements(): array
    {
        return [
            // ── View Mode ──
            '@viewMode'         => '#viewMode',
            '@editMode'         => '#editMode',

            // ── Tombol di topbar ──
            '@btnEdit'          => '#topbarBtnEdit',
            '@btnSave'          => 'button[form="profileForm"]',
            '@btnCancel'        => '#topbarEditActions button:first-child',

            // ── Form fields ──
            '@inputName'        => 'input[name="name"]',
            '@inputAge'         => 'input[name="age"]',
            '@selectGender'     => 'select[name="gender"]',
            '@inputBirthDate'   => 'input[name="birth_date"]',
            '@inputPhone'       => 'input[name="phone"]',
            '@inputAddress'     => 'input[name="address"]',

            // ── Flash messages ──
            '@alertSuccess'     => '.alert-success',
            '@alertError'       => '.alert-error',
        ];
    }

    // ── Helpers ─────────────────────────────────────────────

    /** Klik tombol "Edit" di topbar untuk masuk mode edit */
    public function clickEdit(Browser $browser): void
    {
        $browser->click('@btnEdit');
    }

    /** Submit form profil */
    public function clickSave(Browser $browser): void
    {
        $browser->click('@btnSave');
    }

    /** Klik "Batal" untuk kembali ke view mode */
    public function clickCancel(Browser $browser): void
    {
        $browser->click('@btnCancel');
    }

    /** Pastikan sedang dalam view mode (bukan form) */
    public function assertInViewMode(Browser $browser): void
    {
        $browser->assertVisible('@viewMode')
                ->assertMissing('@editMode');
    }

    /** Pastikan sedang dalam edit mode (form terbuka) */
    public function assertInEditMode(Browser $browser): void
    {
        $browser->assertVisible('@editMode')
                ->assertMissing('@viewMode');
    }
}
