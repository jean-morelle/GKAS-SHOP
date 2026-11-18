<?php

namespace App\Console\Commands;

use App\Services\SubscriptionMonitor;
use Illuminate\Console\Command;

class CheckAccess extends Command
{
    protected $signature = 'gkas:check-access';

    protected $description = 'Contrôle les essais et abonnements : rappels J-7/J-3/J-1 et notifications d\'expiration (sans doublon)';

    public function handle(SubscriptionMonitor $monitor): int
    {
        $stats = $monitor->run();
        $this->info("Rappels envoyés : {$stats['reminders']} — Expirations notifiées : {$stats['expirations']}");

        return self::SUCCESS;
    }
}
