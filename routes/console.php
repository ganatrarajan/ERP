<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Run nightly at midnight (12 AM) to check holidays and insert H attendance
Schedule::call(function () {
    \App\Services\HolidayService::autoMarkHolidayAttendance(today()->format('Y-m-d'));
})->dailyAt('00:00');

// CLI command to manually trigger the holiday marking for any date
Artisan::command('holiday:mark {date?}', function ($date = null) {
    $date = $date ?: today()->format('Y-m-d');
    $this->info("Checking holidays and marking H attendance for date: {$date}...");
    \App\Services\HolidayService::autoMarkHolidayAttendance($date);
    $this->info("Holiday marking completed.");
})->purpose('Auto-mark Holiday H status for students and staff');
