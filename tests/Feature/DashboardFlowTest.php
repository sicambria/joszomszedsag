<?php

namespace Tests\Feature;

use App\User;
use App\UserSupportData;
use App\UserVolunteerData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DashboardFlowTest extends TestCase
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

    private function makeUser(int $roleId = 6): User
    {
        $addressId = DB::table('addresses')->insertGetId([
            'post_code' => '1111',
            'city' => 'Budapest',
            'street' => 'Fo utca',
            'house_number' => '1',
            'house_number_2' => null,
        ]);

        return User::factory()->create([
            'role_id' => $roleId,
            'home_address_id' => $addressId,
            'first_name' => 'Teszt',
            'last_name' => 'Elek',
            'display_name' => 'tesztelek',
        ]);
    }

    public function test_dashboard_pages_render_for_verified_user()
    {
        $user = $this->makeUser();
        $this->be($user);

        foreach ([
            '/admin',
            '/admin/sajat-segitsegkeresek',
            '/admin/leadott-segitsegkeresek',
            '/admin/nyitott-segitsegkeresek',
            '/admin/uj-segitsegkeres',
            '/admin/vigyazok',
            '/admin/profile',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/profile')->assertRedirect('/login');
    }

    public function test_save_profile_updates_user_and_volunteer_data()
    {
        $user = $this->makeUser();
        \App\UserVolunteerData::create([
            'user_id' => $user->id,
            'has_car' => 0,
            'availability' => 'Délelőtt',
            'helping_groups' => json_encode([1]),
            'status' => 1,
        ]);
        $this->be($user);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post('/admin/profile', [
                'display_name' => 'ujnev',
                'email' => $user->email,
                'phone' => '+3611111111',
                'postcode' => '1111',
                'city' => 'Budapest',
                'street' => 'Mellek utca',
                'house_number' => '3',
                'facebook_profile' => 'https://facebook.com/x',
                'has_car' => 'on',
                'availability' => 'Délután',
                'area_postcode' => ['1111'],
                'area_city' => ['Budapest'],
                'area_street' => ['Mellek utca'],
                'area_from-house-number' => ['1'],
                'area_to-house-number' => ['9'],
                'help_types' => [1],
            ]);

        $response->assertRedirect(route('admin_profile'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'display_name' => 'ujnev']);
        $this->assertDatabaseHas('user_data_volunteer', ['user_id' => $user->id, 'status' => 1]);
    }

    public function test_save_support_data_creates_request_and_unmatched_status()
    {
        $user = $this->makeUser(7);
        $this->be($user);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post('/admin/save_support_data', [
                'facebook_profile' => 'https://facebook.com/x',
                'is_healthy' => 'on',
                'help_types' => 1,
                'privacy_policy' => 'on',
                'situation_desc' => 'Segítség kell.',
            ]);

        $response->assertRedirect(route('admin_open_help_request'));
        // No volunteers yet -> DashboardController marks unmatched (status 0).
        $this->assertDatabaseHas('user_data_support', ['user_id' => $user->id, 'status' => 0]);
    }

    public function test_volunteer_take_and_release_flow()
    {
        $owner = $this->makeUser(7);
        $volunteer = $this->makeUser(6);

        $support = UserSupportData::factory()->create([
            'user_id' => $owner->id,
            'status' => 2,
        ]);

        $this->be($volunteer);
        $take = $this->csrfExemptPost('/admin/volunteer_for_support_ajax', [
            'id' => $support->id,
            'status' => 3,
        ]);
        $take->assertOk();
        $this->assertDatabaseHas('user_data_support', [
            'id' => $support->id,
            'status' => 3,
            'volunteer_user_id' => $volunteer->id,
        ]);

        $release = $this->csrfExemptPost('/admin/volunteer_for_support_ajax', [
            'id' => $support->id,
            'status' => 0,
        ]);
        $release->assertOk();
        $this->assertDatabaseHas('user_data_support', [
            'id' => $support->id,
            'status' => 0,
            'volunteer_user_id' => null,
        ]);
    }
}
