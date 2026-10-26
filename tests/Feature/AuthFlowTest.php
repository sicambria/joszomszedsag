<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders()
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_user_can_login_with_password()
    {
        $user = User::factory()->create(['email' => 'auth@example.com']);

        $response = $this->csrfExemptPost('/login', [
            'email' => 'auth@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_email_fails_cleanly()
    {
        $response = $this->csrfExemptPost('/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        // Was a fatal `null->password` deref before the L12 port (BUG).
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_first_login_adopts_password_for_form_registered_user()
    {
        $user = User::factory()->create([
            'email' => 'nopass@example.com',
            'password' => null,
        ]);

        $response = $this->csrfExemptPost('/login', [
            'email' => 'nopass@example.com',
            'password' => 'uj-jelszo-123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('uj-jelszo-123', $user->fresh()->password)
        );
    }

    public function test_logout_works()
    {
        $user = User::factory()->create();
        $this->be($user);

        $this->csrfExemptPost('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
