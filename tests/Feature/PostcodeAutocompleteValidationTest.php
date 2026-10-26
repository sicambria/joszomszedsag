<?php

namespace Tests\Feature;

use Tests\TestCase;

class PostcodeAutocompleteValidationTest extends TestCase
{
    public function test_autocomplete_rejects_missing_postcode()
    {
        $response = $this->post('/postcode-autocomplete', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('post_code');
    }

    public function test_autocomplete_rejects_injection_payload()
    {
        $response = $this->post('/postcode-autocomplete', ['post_code' => '1" OR "1"="1']);
        // Fails size:1 + regex validation before any SQL is built.
        $response->assertStatus(302);
        $response->assertSessionHasErrors('post_code');
    }

    public function test_autocomplete_rejects_two_digit_prefix()
    {
        $response = $this->post('/postcode-autocomplete', ['post_code' => '10']);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('post_code');
    }
}
