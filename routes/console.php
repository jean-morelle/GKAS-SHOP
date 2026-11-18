<?php

use Illuminate\Support\Facades\Schedule;

// Contrôle des échéances d'essai / d'abonnement (rappels et expirations).
// En local sous Windows : `php artisan schedule:work` (voir README).
Schedule::command('gkas:check-access')->hourly()->withoutOverlapping();
