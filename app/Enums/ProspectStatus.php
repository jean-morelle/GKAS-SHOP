<?php

namespace App\Enums;

enum ProspectStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case DemoPlanned = 'demo_planned';
    case TrialCreated = 'trial_created';
    case TrialRunning = 'trial_running';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau prospect',
            self::Contacted => 'Contacté',
            self::DemoPlanned => 'Démonstration planifiée',
            self::TrialCreated => 'Essai créé',
            self::TrialRunning => 'Essai en cours',
            self::Converted => 'Converti en client',
            self::Lost => 'Perdu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'indigo',
            self::DemoPlanned => 'purple',
            self::TrialCreated => 'cyan',
            self::TrialRunning => 'amber',
            self::Converted => 'green',
            self::Lost => 'gray',
        };
    }
}
