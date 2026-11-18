<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Journal des opérations sensibles (création de boutique, rôles,
 * suspensions, prolongations, paiements…).
 */
class ActivityLogger
{
    public static function log(string $action, string $description, ?Model $subject = null, array $properties = []): ActivityLog
    {
        $log = new ActivityLog;
        $log->forceFill([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ])->save();

        return $log;
    }
}
