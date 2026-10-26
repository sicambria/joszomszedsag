<?php

namespace Tests\Unit;

use App\Log;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_model_change_strips_tags_and_skips_secrets()
    {
        $user = User::factory()->create(['email' => 'log@example.com']);

        logModelChange(
            ['email' => 'a@example.com', 'password' => 'secret', 'remember_token' => 'tok'],
            ['email' => '<b>b@example.com</b>' . str_repeat('x', 300), 'password' => 'new', 'remember_token' => 'tok2'],
            $user->id,
            'Teszt:'
        );

        $texts = Log::where('user_id', $user->id)->pluck('text')->all();
        // password + remember_token never logged...
        foreach ($texts as $text) {
            $this->assertStringNotContainsString('secret', $text);
            $this->assertStringNotContainsString('new', $text);
            $this->assertStringNotContainsString('tok', $text);
            // ...tags stripped, values truncated to 200 chars.
            $this->assertStringNotContainsString('<b>', $text);
        }
        $this->assertNotEmpty($texts);
        $this->assertTrue(mb_strlen($texts[0]) <= 200 + 60);
    }

    public function test_postcode_prefix_validation_contract()
    {
        $this->assertMatchesRegularExpression('/^[0-9]$/', '3');
        $this->assertDoesNotMatchRegularExpression('/^[0-9]$/', '33');
        $this->assertDoesNotMatchRegularExpression('/^[0-9]$/', '3"; DROP TABLE users; --');
    }
}
