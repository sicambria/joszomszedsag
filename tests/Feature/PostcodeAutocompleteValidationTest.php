<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostcodeAutocompleteValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_autocomplete_rejects_missing_postcode()
    {
        $response = $this->csrfExemptPost('/postcode-autocomplete', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('post_code');
    }

    public function test_autocomplete_rejects_injection_payload()
    {
        $response = $this->csrfExemptPost('/postcode-autocomplete', ['post_code' => '1" OR "1"="1']);
        // Fails size:1 + regex validation before any SQL is built.
        $response->assertStatus(302);
        $response->assertSessionHasErrors('post_code');
    }

    public function test_autocomplete_rejects_two_digit_prefix()
    {
        $response = $this->csrfExemptPost('/postcode-autocomplete', ['post_code' => '10']);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('post_code');
    }

    public function test_autocomplete_returns_json_for_valid_prefix()
    {
        $response = $this->csrfExemptPost('/postcode-autocomplete', ['post_code' => '1']);
        $response->assertOk();
        $response->assertJson([]);
    }
}
