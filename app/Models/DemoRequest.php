<?php

namespace App\Models;

use App\Enums\ProspectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DemoRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'business_name', 'phone', 'email', 'activity', 'social_network',
        'social_handle', 'city', 'message',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProspectStatus::class,
            'next_follow_up_at' => 'date',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CommercialNote::class)->latest('id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'demo' => 'Demande d\'essai',
            'contact' => 'Message de contact',
            default => 'Saisie manuelle',
        };
    }

    public static function socialNetworks(): array
    {
        return ['tiktok' => 'TikTok', 'facebook' => 'Facebook', 'instagram' => 'Instagram', 'whatsapp' => 'WhatsApp', 'autre' => 'Autre'];
    }
}
