<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_submit_rejects_invalid_role()
    {
        $response = $this->post('/form-submit', ['user_role' => 1]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('user_role');
    }

    public function test_form_submit_rejects_missing_role()
    {
        $response = $this->post('/form-submit', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('user_role');
    }
}
