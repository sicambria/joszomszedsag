<?php

namespace Tests\Feature;

use App\User;
use App\UserSupportData;
use App\UserVolunteerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        DB::table('available_post_codes')->insert(['post_code' => '1111']);
        DB::table('cities')->insert(['post_code' => '1111', 'name' => 'Budapest']);
        Notification::fake();
    }

    private function volunteerPayload(): array
    {
        return [
            'user_role' => 6,
            'first_name' => 'Teszt',
            'last_name' => 'Elek',
            'display_name' => 'tesztelek',
            'birth_year' => '1990',
            'email' => 'volunteer@example.com',
            'phone' => '+3612345678',
            'postcode' => '1111',
            'city' => 'Budapest',
            'street' => 'Fo utca',
            'house_number' => '1',
            'facebook_profile' => 'https://facebook.com/teszt',
            'availability' => 'Délelőtt',
            'area_postcode' => ['1111'],
            'area_city' => ['Budapest'],
            'area_street' => ['Fo utca'],
            'area_from-house-number' => ['1'],
            'area_to-house-number' => ['5'],
            'help_types' => [1, 2],
            'privacy_policy' => 'on',
            'health_checkbox' => 'on',
        ];
    }

    private function supportPayload(): array
    {
        return [
            'user_role' => 7,
            'first_name' => 'Kérő',
            'last_name' => 'Anna',
            'display_name' => 'keroanna',
            'birth_year' => '1950',
            'email' => 'requester@example.com',
            'phone' => '+3698765432',
            'postcode' => '1111',
            'city' => 'Budapest',
            'street' => 'Fo utca',
            'house_number' => '2',
            'help_types' => 1,
            'privacy_policy' => 'on',
            'is_healthy' => 'on',
            'situation_desc' => 'Bevásárlásban kérek segítséget.',
        ];
    }

    public function test_volunteer_registration_creates_user_data_and_address()
    {
        $response = $this->csrfExemptPost('/form-submit', $this->volunteerPayload());

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'volunteer@example.com', 'role_id' => 6]);
        $user = User::where('email', 'volunteer@example.com')->first();
        $this->assertNotNull($user->home_address_id);
        $this->assertDatabaseHas('user_data_volunteer', ['user_id' => $user->id, 'status' => 1]);
        $volunteerData = UserVolunteerData::where('user_id', $user->id)->first();
        $this->assertSame([1, 2], json_decode($volunteerData->helping_groups, true));
        $this->assertDatabaseHas('logs', ['user_id' => $user->id]);
    }

    public function test_support_registration_creates_help_request()
    {
        $response = $this->csrfExemptPost('/form-submit', $this->supportPayload());

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['email' => 'requester@example.com', 'role_id' => 7]);
        $user = User::where('email', 'requester@example.com')->first();
        $this->assertDatabaseHas('user_data_support', [
            'user_id' => $user->id,
            'help_request' => 1,
            'status' => 2,
        ]);
    }

    public function test_support_registration_without_volunteers_keeps_status_new()
    {
        $this->csrfExemptPost('/form-submit', $this->supportPayload());

        $user = User::where('email', 'requester@example.com')->first();
        // No volunteers cover Fo utca 2 -> nobody notified, request stays new.
        $this->assertDatabaseHas('user_data_support', ['user_id' => $user->id, 'status' => 2]);
    }

    public function test_volunteer_registration_rejects_bad_email()
    {
        $payload = $this->volunteerPayload();
        $payload['email'] = 'not-an-email';
        $response = $this->csrfExemptPost('/form-submit', $payload);
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['display_name' => 'tesztelek']);
    }
}
