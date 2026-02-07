<?php

namespace App\Console\Commands;

use App\Events\RentalDueReminder;
use App\Models\Rental;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckRentalDueDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rental:check-due-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check rental due dates and create notifications for 1 day before and on the due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $today = Carbon::now()->toDateString();
            $tomorrow = Carbon::now()->addDay()->toDateString();

            // Check for rentals due today
            $rentalsToday = Rental::where('due_date', $today)
                ->where('status', '!=', 'returned')
                ->where('status', '!=', 'rejected')
                ->with('camera')
                ->get();

            foreach ($rentalsToday as $rental) {
                RentalDueReminder::dispatch($rental, 'due_date_today');
                $this->info("Notification event dispatched for rental ID {$rental->id} - Due today");
            }

            // Check for rentals due tomorrow (1 day before)
            $rentalsTomorrow = Rental::where('due_date', $tomorrow)
                ->where('status', '!=', 'returned')
                ->where('status', '!=', 'rejected')
                ->with('camera')
                ->get();

            foreach ($rentalsTomorrow as $rental) {
                RentalDueReminder::dispatch($rental, 'due_date_reminder');
                $this->info("Notification event dispatched for rental ID {$rental->id} - Due tomorrow");
            }

            $this->info('Rental due date check completed successfully!');
            Log::info('Rental due date check completed at ' . now());

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Error in rental due date check: ' . $e->getMessage());
        }
    }
}
