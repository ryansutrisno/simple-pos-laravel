<?php

use Illuminate\Support\Facades\Schedule;

// Expire store credits daily at midnight
Schedule::command('store-credits:expire')->daily();

// Run database backup daily at 2:00 AM
Schedule::command('backup:run --only-db')->dailyAt('02:00');

// Cleanup old backups daily at 3:00 AM
Schedule::command('backup:clean')->dailyAt('03:00');
