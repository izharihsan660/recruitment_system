<?php

use App\Services\PreboardingService;
use App\Services\ProbationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(PreboardingService::class)->sendH7Reminders())->dailyAt('08:00');
Schedule::call(fn () => app(ProbationService::class)->sendH7Reminders())->dailyAt('08:00');
