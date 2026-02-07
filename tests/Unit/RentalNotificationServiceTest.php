<?php

namespace Tests\Unit;

use App\Models\Camera;
use App\Models\Notification;
use App\Models\Rental;
use App\Models\User;
use App\Services\RentalNotificationService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\TestCase as BaseTestCase;

class RentalNotificationServiceTest extends BaseTestCase
{
    protected RentalNotificationService $service;
    protected User $user;
    protected Camera $camera;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RentalNotificationService();
        
        // Create test data
        $this->user = User::factory()->create(['role' => 'user']);
        $this->camera = Camera::factory()->create();
    }

    public function test_notify_rentals_due_today()
    {
        // Create a rental due today
        $rental = Rental::factory()->create([
            'user_id' => $this->user->id,
            'camera_id' => $this->camera->id,
            'due_date' => Carbon::now()->toDateString(),
            'status' => 'approved',
        ]);

        // Create notification
        $notifications = $this->service->notifyRentalsDueToday();

        // Assert notification was created
        $this->assertCount(1, $notifications);
        $this->assertEquals('due_date_today', $notifications->first()->type);
        $this->assertEquals($this->user->id, $notifications->first()->user_id);
        $this->assertEquals($rental->id, $notifications->first()->rental_id);
    }

    public function test_notify_rentals_due_tomorrow()
    {
        // Create a rental due tomorrow
        $rental = Rental::factory()->create([
            'user_id' => $this->user->id,
            'camera_id' => $this->camera->id,
            'due_date' => Carbon::now()->addDay()->toDateString(),
            'status' => 'approved',
        ]);

        // Create notification
        $notifications = $this->service->notifyRentalsDueTomorrow();

        // Assert notification was created
        $this->assertCount(1, $notifications);
        $this->assertEquals('due_date_reminder', $notifications->first()->type);
        $this->assertEquals($this->user->id, $notifications->first()->user_id);
    }

    public function test_prevents_duplicate_notifications()
    {
        // Create a rental due today
        $rental = Rental::factory()->create([
            'user_id' => $this->user->id,
            'camera_id' => $this->camera->id,
            'due_date' => Carbon::now()->toDateString(),
            'status' => 'approved',
        ]);

        // Create notification twice
        $this->service->notifyRentalsDueToday();
        $notifications = $this->service->notifyRentalsDueToday();

        // Second call should not create duplicate
        $this->assertCount(0, $notifications);

        // But the notification should exist in database
        $this->assertEquals(1, Notification::where('rental_id', $rental->id)
            ->where('type', 'due_date_today')
            ->count());
    }

    public function test_get_user_unread_notifications()
    {
        // Create multiple notifications
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => true,
        ]);

        $unreadNotifications = $this->service->getUserUnreadNotifications($this->user->id);

        // Assert only unread notifications are returned
        $this->assertCount(2, $unreadNotifications);
        $unreadNotifications->each(function ($notification) {
            $this->assertFalse($notification->is_read);
        });
    }

    public function test_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $result = $this->service->markAsRead($notification->id);

        $this->assertTrue($result);
        $this->assertTrue($notification->refresh()->is_read);
    }

    public function test_mark_all_notifications_as_read()
    {
        // Create multiple unread notifications
        Notification::factory(3)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $count = $this->service->markAllAsRead($this->user->id);

        // Assert all were marked as read
        $this->assertEquals(3, $count);
        $unreadCount = Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count();
        $this->assertEquals(0, $unreadCount);
    }

    public function test_delete_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $result = $this->service->deleteNotification($notification->id);

        $this->assertTrue($result);
        $this->assertNull(Notification::find($notification->id));
    }

    public function test_get_notification_statistics()
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

        $stats = $this->service->getNotificationStats($this->user->id);

        $this->assertEquals(8, $stats['total']);
        $this->assertEquals(5, $stats['unread']);
        $this->assertEquals(3, $stats['read']);
    }

    public function test_ignores_returned_and_rejected_rentals()
    {
        // Create rentals with different statuses
        Rental::factory()->create([
            'user_id' => $this->user->id,
            'camera_id' => $this->camera->id,
            'due_date' => Carbon::now()->toDateString(),
            'status' => 'returned',
        ]);

        Rental::factory()->create([
            'user_id' => $this->user->id,
            'camera_id' => $this->camera->id,
            'due_date' => Carbon::now()->toDateString(),
            'status' => 'rejected',
        ]);

        $notifications = $this->service->notifyRentalsDueToday();

        // Should not create notifications for returned/rejected rentals
        $this->assertCount(0, $notifications);
    }
}
