<?php

namespace Tests\Feature;

use App\User;
use App\UserSupportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VolunteerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_hit_volunteer_ajax()
    {
        $response = $this->post('/admin/volunteer_for_support_ajax', ['id' => 1, 'status' => 3]);
        // Guest is redirected to login by `auth` middleware (not 200 + PII leak).
        $response->assertStatus(302);
    }

    public function test_volunteer_action_rejects_unknown_status()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $support = factory(UserSupportData::class)->create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $response = $this->post('/admin/volunteer_for_support_ajax', [
            'id' => $support->id,
            'status' => 99,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('status');
    }

    public function test_volunteer_action_rejects_missing_record()
    {
        $user = factory(User::class)->create();
        $this->be($user);

        $response = $this->post('/admin/volunteer_for_support_ajax', [
            'id' => 999999,
            'status' => 3,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('id');
    }
}
