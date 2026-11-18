<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialHistory extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'previous_ends_at' => 'datetime',
            'new_ends_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'started' => 'Début de l\'essai',
            'extended' => 'Prolongation',
            'adjusted' => 'Modification de date',
            'expired' => 'Expiration',
            default => $this->action,
        };
    }
}
