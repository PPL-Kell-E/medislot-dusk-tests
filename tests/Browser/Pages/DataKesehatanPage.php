<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;

class DataKesehatanPage extends Page
{
    public function url(): string
    {
        return '/data-kesehatan';
    }

    public function elements(): array
    {
        return [
            '@btnBuatCatatan'   => '.btn-dk',
            '@btnExport'        => 'a.btn-export',

            '@modal'            => '#inputModal',
            '@successModal'     => '#successModal',
            '@successMsg'       => '#successMsg',

            '@inputTB'          => 'input[name="height_cm"]',
            '@inputBB'          => 'input[name="weight_kg"]',
            '@selectBlood'      => 'select[name="blood_type"]',
            '@inputAlergi'      => 'input[name="allergies"]',
            '@inputCheckup'     => 'input[name="recorded_at"]',
            '@inputRiwayat'     => 'input[name="chronic_conditions"]',

            '@btnSimpan'        => '.btn-modal-submit',
            '@btnBatal'         => '.btn-modal-cancel',
            '@btnContinue'      => '.btn-continue',
        ];
    }

    public function openModal(Browser $browser): void
    {
        $browser->click('@btnBuatCatatan')
                ->waitFor('#inputModal.show', 5);
    }

    public function submitForm(Browser $browser): void
    {
        $browser->click('@btnSimpan')
                ->waitFor('#successModal.show', 10);
    }

    public function continueAfterSuccess(Browser $browser): void
    {
        $browser->click('@btnContinue')
                ->waitFor('#viewMode, .dk-card', 5);
    }
}
