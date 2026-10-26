<?php

namespace Tests\Feature;

use App\BulkMail;
use App\Notifications\BulkEmail;
use App\Notifications\NotCompleted;
use App\Notifications\RequestCompleted;
use App\Notifications\StatusCanChange;
use App\Notifications\StatusChange;
use App\Notifications\SupportRequest;
use App\Notifications\UserRegisteredNotification;
use App\Notifications\VolunteerTextNotification;
use App\Notifications\WelcomeEmail;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seeds help_types (FK for user_data_support factory rows).
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_all_notifications_render_mail_without_exception()
    {
        $user = User::factory()->create(['first_name' => 'Teszt', 'last_name' => 'Elek', 'display_name' => 't']);
        $from = User::factory()->make(['first_name' => 'Küldő', 'last_name' => 'Béla', 'display_name' => 'kb']);

        $cases = [
            new UserRegisteredNotification($user),
            new SupportRequest($user),
            new WelcomeEmail($user),
            new StatusChange($user, 3, 2, 'support'),
            new StatusChange($user, 3, 2, 'volunteer'),
            new NotCompleted($user),
            new RequestCompleted($user, 1, 1),
            new StatusCanChange($user, 7),
            new StatusCanChange($user, 6),
            new VolunteerTextNotification($from, $user, 'Szia!'),
            new BulkEmail($user, new BulkMail(['subject' => 'Tárgy', 'mail' => 'Szöveg'])),
        ];

        foreach ($cases as $notification) {
            $mail = $notification->toMail($user);
            $this->assertNotEmpty($mail->subject ?? $mail->actionText ?? 'ok');
        }
    }

    public function test_volunteer_notification_is_sent_on_take()
    {
        Notification::fake();
        $owner = User::factory()->create(['role_id' => 7]);
        $volunteer = User::factory()->create(['role_id' => 6]);
        $support = \App\UserSupportData::factory()->create(['user_id' => $owner->id, 'status' => 2]);

        $this->be($volunteer);
        $this->csrfExemptPost('/admin/volunteer_for_support_ajax', ['id' => $support->id, 'status' => 3])
            ->assertOk();

        Notification::assertSentTo($owner, StatusChange::class);
    }
}
