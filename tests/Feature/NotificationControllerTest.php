<?php

namespace Tests\Feature;

use App\Models\Camera;
use App\Models\Notification;
use App\Models\Rental;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Camera $camera;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'user']);
        $this->camera = Camera::factory()->create();
    }

    public function test_user_can_get_notifications()
    {
        // Create notifications for user
        Notification::factory(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_user_can_filter_unread_notifications()
    {
        // Create notifications with different read status
        Notification::factory(2)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory(1)->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications?is_read=false');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_user_can_filter_by_notification_type()
    {
        // Create notifications with different types
        Notification::factory(2)->create([
            'user_id' => $this->user->id,
            'type' => 'due_date_today',
        ]);
        Notification::factory(1)->create([
            'user_id' => $this->user->id,
            'type' => 'due_date_reminder',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications?type=due_date_today');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_user_can_view_single_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $notification->id);
        $response->assertJsonPath('data.title', $notification->title);
    }

    public function test_user_cannot_view_other_user_notification()
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertTrue($notification->refresh()->is_read);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        // Create unread notifications
        Notification::factory(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/notifications/read-all');

        $response->assertStatus(200);
        
        $unreadCount = Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();
        
        $this->assertEquals(0, $unreadCount);
    }

    public function test_user_can_delete_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200);
        $this->assertNull(Notification::find($notification->id));
    }

    public function test_user_can_delete_all_notifications()
    {
        // Create notifications
        Notification::factory(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/notifications');

        $response->assertStatus(200);
        
        $count = Notification::where('user_id', $this->user->id)->count();
        $this->assertEquals(0, $count);
    }

    public function test_user_can_get_notification_statistics()
    {
        // Create notifications
        Notification::factory(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory(3)->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications/stats/overview');

        $response->assertStatus(200);
        $response->assertJsonPath('data.total', 8);
        $response->assertJsonPath('data.unread', 5);
        $response->assertJsonPath('data.read', 3);
    }

    public function test_user_can_get_unread_count()
    {
        // Create notifications
        Notification::factory(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory(2)->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications/unread/count');

        $response->assertStatus(200);
        $response->assertJsonPath('unread_count', 3);
    }

    public function test_unauthenticated_user_cannot_access_notifications()
    {
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(401);
    }

    public function test_notification_includes_rental_and_camera_data()
    {
        $rental = Rental::factory()->create([
            'user_id' => $this->user->id,
            'camera_id' => $this->camera->id,
        ]);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'rental_id' => $rental->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.rental.id', $rental->id);
        $response->assertJsonPath('data.camera.id', $this->camera->id);
    }
}
