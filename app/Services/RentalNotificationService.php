<?php

namespace App\Services;

use App\Models\Rental;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RentalNotificationService
{
    /**
     * Create notifications for rentals due today
     * 
     * @return Collection
     */
    public function notifyRentalsDueToday(): Collection
    {
        $today = Carbon::now()->toDateString();
        
        $rentalsToday = Rental::where('due_date', $today)
            ->where('status', '!=', 'returned')
            ->where('status', '!=', 'rejected')
            ->get();

        $createdNotifications = collect();

        foreach ($rentalsToday as $rental) {
            $existingNotification = Notification::where('rental_id', $rental->id)
                ->where('type', 'due_date_today')
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingNotification) {
                $notification = Notification::create([
                    'user_id' => $rental->user_id,
                    'rental_id' => $rental->id,
                    'title' => 'Rental Due Today',
                    'message' => 'Your rental for ' . $rental->camera->name . ' is due today (' . $rental->due_date . '). Please return it on time.',
                    'type' => 'due_date_today',
                    'is_read' => false,
                ]);

                $createdNotifications->push($notification);
            }
        }

        return $createdNotifications;
    }

    /**
     * Create notifications for rentals due tomorrow (1 day before)
     * 
     * @return Collection
     */
    public function notifyRentalsDueTomorrow(): Collection
    {
        $today = Carbon::now()->toDateString();
        $tomorrow = Carbon::now()->addDay()->toDateString();

        $rentalsTomorrow = Rental::where('due_date', $tomorrow)
            ->where('status', '!=', 'returned')
            ->where('status', '!=', 'rejected')
            ->get();

        $createdNotifications = collect();

        foreach ($rentalsTomorrow as $rental) {
            $existingNotification = Notification::where('rental_id', $rental->id)
                ->where('type', 'due_date_reminder')
                ->whereDate('created_at', $today)
                ->exists();

            if (!$existingNotification) {
                $notification = Notification::create([
                    'user_id' => $rental->user_id,
                    'rental_id' => $rental->id,
                    'title' => 'Rental Due Tomorrow',
                    'message' => 'Your rental for ' . $rental->camera->name . ' is due tomorrow (' . $rental->due_date . '). Please prepare to return it.',
                    'type' => 'due_date_reminder',
                    'is_read' => false,
                ]);

                $createdNotifications->push($notification);
            }
        }

        return $createdNotifications;
    }

    /**
     * Create notifications for all due rentals
     * 
     * @return array
     */
    public function createAllDueNotifications(): array
    {
        return [
            'due_today' => $this->notifyRentalsDueToday(),
            'due_tomorrow' => $this->notifyRentalsDueTomorrow(),
        ];
    }

    /**
     * Get all unread notifications for a user
     * 
     * @param int $userId
     * @return Collection
     */
    public function getUserUnreadNotifications(int $userId): Collection
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Mark notification as read
     * 
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead(int $notificationId): bool
    {
        return Notification::findOrFail($notificationId)
            ->update(['is_read' => true]);
    }

    /**
     * Mark all notifications as read for a user
     * 
     * @param int $userId
     * @return int
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Delete notification
     * 
     * @param int $notificationId
     * @return bool|null
     */
    public function deleteNotification(int $notificationId): bool|null
    {
        return Notification::findOrFail($notificationId)->delete();
    }

    /**
     * Get notification statistics
     * 
     * @param int $userId
     * @return array
     */
    public function getNotificationStats(int $userId): array
    {
        $totalNotifications = Notification::where('user_id', $userId)->count();
        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        return [
            'total' => $totalNotifications,
            'unread' => $unreadCount,
            'read' => $totalNotifications - $unreadCount,
        ];
    }
}
