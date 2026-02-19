<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Accorder les points de fidélité 2h après le départ du bus — toutes les heures
Schedule::command('bookings:award-loyalty-points')->hourly();
