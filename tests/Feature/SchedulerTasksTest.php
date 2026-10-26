<?php

namespace Tests\Feature;

use App\User;
use App\UserSupportData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SchedulerTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        Notification::fake();
    }

    public function test_request_completed_closes_stale_confirmations()
    {
        $requester = User::factory()->create();
        $volunteer = User::factory()->create();

        $support = UserSupportData::factory()->create([
            'user_id' => $requester->id,
            'volunteer_user_id' => $volunteer->id,
            'status' => 4,
        ]);
        DB::table('user_data_support')->where('id', $support->id)->update([
            'updated_at' => now()->subDays(2)->toDateTimeString(),
        ]);

        UserSupportData::requestCompleted();

        $this->assertDatabaseHas('user_data_support', ['id' => $support->id, 'status' => 1]);
        Notification::assertSentTo($requester, \App\Notifications\RequestCompleted::class);
        Notification::assertSentTo($volunteer, \App\Notifications\RequestCompleted::class);
    }

    public function test_request_completed_ignores_fresh_requests()
    {
        $support = UserSupportData::factory()->create(['status' => 4]);

        UserSupportData::requestCompleted();

        $this->assertDatabaseHas('user_data_support', ['id' => $support->id, 'status' => 4]);
    }

    public function test_cron_emails_skips_completed_and_unassigned()
    {
        $volunteer = User::factory()->create();
        $done = UserSupportData::factory()->create([
            'volunteer_user_id' => $volunteer->id,
            'status' => 1,
        ]);
        $unassigned = UserSupportData::factory()->create([
            'volunteer_user_id' => null,
            'status' => 2,
        ]);

        UserSupportData::cronEmails();

        $this->assertDatabaseMissing('notifications', ['user_support_id' => $done->id]);
        $this->assertDatabaseMissing('notifications', ['user_support_id' => $unassigned->id]);
    }
}
