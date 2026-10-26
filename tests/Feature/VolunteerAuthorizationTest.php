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
        $response = $this->csrfExemptPost('/admin/volunteer_for_support_ajax', ['id' => 1, 'status' => 3]);
        // Guest is redirected to login by `auth` middleware (not 200 + PII leak).
        $response->assertRedirect('/login');
    }

    public function test_volunteer_action_rejects_unknown_status()
    {
        $user = User::factory()->create();
        $this->be($user);

        $support = UserSupportData::factory()->create([
            'user_id' => $user->id,
            'status' => 2,
        ]);

        $response = $this->csrfExemptPost('/admin/volunteer_for_support_ajax', [
            'id' => $support->id,
            'status' => 99,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('status');
    }

    public function test_volunteer_action_rejects_missing_record()
    {
        $user = User::factory()->create();
        $this->be($user);

        $response = $this->csrfExemptPost('/admin/volunteer_for_support_ajax', [
            'id' => 999999,
            'status' => 3,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasErrors('id');
    }

    public function test_stranger_cannot_take_over_or_read_pii()
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $support = UserSupportData::factory()->create([
            'user_id' => $owner->id,
            'status' => 2,
            'volunteer_user_id' => null,
        ]);

        $this->be($stranger);
        // Status 3 (take task) is open to any verified volunteer...
        $response = $this->csrfExemptPost('/admin/volunteer_for_support_ajax', [
            'id' => $support->id,
            'status' => 3,
        ]);
        $response->assertOk();

        // ...but reading another request's contact details is forbidden
        // unless owner, assigned volunteer, or admin.
        $other = UserSupportData::factory()->create(['user_id' => $owner->id, 'status' => 2]);
        $response = $this->csrfExemptPost('/admin/get_more_user_details_ajax', [
            'id' => $other->id,
            'type' => 7,
        ]);
        $response->assertForbidden();
    }
}
