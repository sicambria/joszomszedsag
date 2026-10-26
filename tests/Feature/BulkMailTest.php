<?php

namespace Tests\Feature;

use App\BulkMail;
use App\Role;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BulkMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        Notification::fake();
    }

    public function test_non_admin_gets_403()
    {
        $user = User::factory()->create(['role_id' => 6]);
        $this->be($user);

        $this->get('/admin/korlevelek')->assertForbidden();
        $this->get('/admin/korlevelek/uj')->assertForbidden();
    }

    public function test_admin_can_list_create_and_send()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $this->be($admin);

        $this->get('/admin/korlevelek')->assertOk();
        $this->get('/admin/korlevelek/uj')->assertOk();

        $target = User::factory()->create(['role_id' => 6]);

        $response = $this->csrfExemptPost('/admin/korlevelek', [
            'groups' => [6],
            'subject' => 'Teszt körlevél',
            'mail' => 'Sziasztok!',
        ]);

        $response->assertRedirect(route('bulk_mail_index'));
        $this->assertDatabaseHas('bulk_mails', ['subject' => 'Teszt körlevél']);
        Notification::assertSentTo($target, \App\Notifications\BulkEmail::class);
    }

    public function test_store_validates_groups()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $this->be($admin);

        $response = $this->csrfExemptPost('/admin/korlevelek', [
            'groups' => [999],
            'subject' => 'x',
            'mail' => 'y',
        ]);
        $response->assertSessionHasErrors('groups.0');
    }
}
