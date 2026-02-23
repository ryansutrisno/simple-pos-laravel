<?php

use Illuminate\Support\Facades\Schedule;

// Expire store credits daily at midnight
Schedule::command('store-credits:expire')->daily();
