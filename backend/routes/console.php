<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Scheduled tasks. Each deployed environment must run `php artisan schedule:run`
 * every minute (cron or a scheduler container — to be added before production).
 */
Schedule::command('sanctum:prune-expired --hours=24')->daily();
