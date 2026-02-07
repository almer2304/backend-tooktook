<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the rental due date check to run daily at 9:00 AM
Schedule::command('rental:check-due-dates')
    ->dailyAt('09:00')
    ->description('Check rental due dates and create notifications');
