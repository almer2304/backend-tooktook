<?php

namespace App\Events;

use App\Models\Rental;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RentalDueReminder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Rental $rental,
        public string $type = 'due_date_reminder', // 'due_date_reminder' or 'due_date_today'
    ) {}
}
