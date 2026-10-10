<?php

namespace App\Policies;

use App\Models\HealthForm;
use App\Models\User;

class HealthFormPolicy
{
    /**
     * Session-Schlüssel mit den IDs der Gesundheitsblätter, die per Lager- und persönlichem Code freigeschaltet wurden.
     */
    public const UNLOCKED_SESSION_KEY = 'unlocked_health_forms';

    /**
     * Gesundheitsblatt ansehen und bearbeiten: mit Lager- und persönlichem Code (auch als Gast)
     * oder als Lagerleitung des Lagers, zu dem das Blatt gehört.
     */
    public function access(?User $user, HealthForm $healthForm): bool
    {
        if (in_array($healthForm->id, session(self::UNLOCKED_SESSION_KEY, []), true)) {
            return true;
        }

        return $user !== null
            && $user->isManager()
            && (int) $user->camp_id === (int) $healthForm->camp_id;
    }
}
