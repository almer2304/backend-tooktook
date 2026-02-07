<?php

namespace App\Listeners;

use App\Events\RentalDueReminder;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateRentalDueNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(RentalDueReminder $event): void
    {
        try {
            $rental = $event->rental;
            $type = $event->type;

            // Check if notification already exists to prevent duplicates
            $existingNotification = Notification::where('rental_id', $rental->id)
                ->where('type', $type)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($existingNotification) {
                Log::info("Notification already exists for rental {$rental->id} with type {$type}");
                return;
            }

            // Create notification based on type
            $notification = match($type) {
                'due_date_today' => [
                    'user_id' => $rental->user_id,
                    'rental_id' => $rental->id,
                    'title' => 'Rental Due Today',
                    'message' => "Your rental for {$rental->camera->name} is due today ({$rental->due_date}). Please return it on time.",
                    'type' => 'due_date_today',
                    'is_read' => false,
                ],
                'due_date_reminder' => [
                    'user_id' => $rental->user_id,
                    'rental_id' => $rental->id,
                    'title' => 'Rental Due Tomorrow',
                    'message' => "Your rental for {$rental->camera->name} is due tomorrow ({$rental->due_date}). Please prepare to return it.",
                    'type' => 'due_date_reminder',
                    'is_read' => false,
                ],
                default => null,
            };

            if ($notification) {
                Notification::create($notification);
                Log::info("Notification created for rental {$rental->id} with type {$type}");
            }

        } catch (\Exception $e) {
            Log::error("Error creating notification: {$e->getMessage()}");
            $this->fail($e);
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function timeout(): int
    {
        return 60;
    }

    /**
     * Determine the number of seconds the job should be retried after all attempts are exhausted.
     */
    public function retryUntil(): \Illuminate\Support\Carbon
    {
        return now()->addDay();
    }
}
