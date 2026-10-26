<?php

namespace Tests\Feature;

use App\Error;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render()
    {
        $this->get('/')->assertOk();
        $this->get('/onkentes-regisztracio')->assertOk();
        $this->get('/segitseg-keres')->assertOk();
        $this->get('/jogi_nyilatkozat')->assertOk();
        $this->get('/adatvedelmi_nyilatkozat')->assertOk();
        $this->get('/cookie_hasznalati_szabalyzat')->assertOk();
        $this->get('/hibabejelentes')->assertOk();
    }

    public function test_logged_in_user_is_sent_to_dashboard_from_forms()
    {
        $user = \App\User::factory()->create();
        $this->be($user);

        $this->get('/onkentes-regisztracio')->assertRedirect(route('admin_home'));
        $this->get('/segitseg-keres')->assertRedirect(route('admin_home'));
    }

    public function test_error_report_validates_and_stores()
    {
        $bad = $this->csrfExemptPost('/hiba_bekuldes', [
            'email' => 'not-an-email',
            'desc' => 'x',
            'privacy_policy' => 'on',
        ]);
        $bad->assertSessionHasErrors('email');

        $ok = $this->csrfExemptPost('/hiba_bekuldes', [
            'email' => 'reporter@example.com',
            'desc' => 'Valami elromlott.',
            'privacy_policy' => 'on',
        ]);
        $ok->assertRedirect(route('error_reporting'));
        $this->assertDatabaseHas('errors', ['email' => 'reporter@example.com', 'status' => 0]);
        $this->assertSame('Valami elromlott.', Error::first()->description);
    }
}
